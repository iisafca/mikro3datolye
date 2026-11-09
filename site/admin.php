<?php require 'config.php';
/* Mikro 3D Atölye yönetim paneli: stok, baskılar, satışlar, ürünler. */
date_default_timezone_set('Europe/Istanbul');
header('X-Robots-Tag: noindex');
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16));
$msg=$_SESSION['flash']??''; unset($_SESSION['flash']);

const STATUS=['ok'=>'Başarılı','fire'=>'Hatalı / fire','test'=>'Deneme'];
const CHANNELS=['Shopier','Dolap','Instagram','WhatsApp','Elden','Diğer'];
const AYLAR=['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

$t=$_GET['t']??'m'; if(!in_array($t,['m','b','s','p'],true)) $t='m';
$ay=$_GET['ay']??date('Y-m'); if($ay!=='hepsi' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$ay)) $ay=date('Y-m');

/* "Bu cihazda açık kalsın": imzalı çerez. Şifre değişirse tüm cihazlardaki çerezler geçersiz olur. */
const KEEP_DAYS=60;
function keepTok($exp){ global $env; return $exp.'.'.hash_hmac('sha256','m3d-admin|'.$exp,(string)$env['ADMIN_PASS']); }
function keepSet($exp){ setcookie('m3d_keep',$exp?keepTok($exp):'',['expires'=>$exp?:1,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']); }
if(empty($_SESSION['ok']) && !empty($env['ADMIN_PASS']) && !empty($_COOKIE['m3d_keep'])){
  $kc=(string)$_COOKIE['m3d_keep']; $ke=explode('.',$kc,2)[0];
  if(ctype_digit($ke) && (int)$ke>time() && hash_equals(keepTok((int)$ke),$kc)){ session_regenerate_id(true); $_SESSION['ok']=1; }
  else keepSet(0);
}

if(isset($_POST['login'])){
  if(!empty($env['ADMIN_PASS']) && hash_equals($env['ADMIN_PASS'],(string)$_POST['pass'])){
    session_regenerate_id(true); $_SESSION['ok']=1; if(!empty($_POST['keep'])) keepSet(time()+KEEP_DAYS*86400);
    header('Location: admin.php'); exit; }
  $msg='Şifre hatalı.'; usleep(600000);
}
if(isset($_GET['out'])){ keepSet(0); session_destroy(); header('Location: admin.php'); exit; }

/* ---------- Yardımcılar ---------- */
// "1.250,50" ya da "1250.5" gibi girişleri sayıya çevirir.
function num($v){ $v=trim((string)$v); if($v==='') return 0.0; if(strpos($v,',')!==false){ $v=str_replace(['.',' '],'',$v); $v=str_replace(',','.',$v); } return (float)$v; }
// Sayıyı gereksiz sıfırlar olmadan Türkçe biçimde yazar: 1.250,5
function q($n){ return rtrim(rtrim(number_format((float)$n,2,',','.'),'0'),','); }
function day($d){ return $d ? date('d.m.Y',strtotime($d)) : ''; }
function dt($v){ $v=trim((string)$v); return preg_match('/^\d{4}-\d{2}-\d{2}$/',$v) ? $v : date('Y-m-d'); }
function hexok($v){ $v=trim((string)$v); return preg_match('/^#[0-9a-fA-F]{6}$/',$v) ? strtolower($v) : '#9aa3ab'; }
function ucost($m){ return (float)$m['pack_size']>0 ? (float)$m['pack_price']/(float)$m['pack_size'] : 0.0; }
function ulabel($m){ $u=ucost($m); if($u<=0) return '—'; return $m['unit']==='g' ? tl($u*1000).'/kg' : tl($u).'/'.h($m['unit']); }
function pcost($p){ return ((float)$p['material_cost']+(float)$p['power_cost']+(float)$p['extra_cost'])/max(1,(int)$p['qty']); }
function sw($hex){ return '<span class="sw" style="--c:'.h($hex?:'#9aa3ab').'"></span>'; }
function back($m=''){ global $t,$ay; if($m!=='') $_SESSION['flash']=$m; header('Location: admin.php?t='.$t.($ay!==date('Y-m')?'&ay='.$ay:'')); exit; }
function per($col){ global $ay; if($ay==='hepsi') return ['1=1',[]]; $f=$ay.'-01'; return ["$col BETWEEN ? AND ?",[$f,date('Y-m-t',strtotime($f))]]; }
function plabel(){ global $ay; if($ay==='hepsi') return 'Tüm zamanlar'; [$y,$m]=explode('-',$ay); return AYLAR[(int)$m].' '.$y; }
function saveImg($f){
  if(!$f||$f['error']!==0)return null; $i=@getimagesize($f['tmp_name']);
  if(!$i||!in_array($i[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP]))return null;
  $s=match($i[2]){IMAGETYPE_JPEG=>imagecreatefromjpeg($f['tmp_name']),IMAGETYPE_PNG=>imagecreatefrompng($f['tmp_name']),default=>imagecreatefromwebp($f['tmp_name'])};
  $k=min(1,1000/max($i[0],$i[1])); $d=imagescale($s,max(1,(int)($i[0]*$k)),max(1,(int)($i[1]*$k)));
  if(!is_dir(__DIR__.'/uploads'))mkdir(__DIR__.'/uploads',0755); $n=bin2hex(random_bytes(8)).'.webp'; imagewebp($d,__DIR__.'/uploads/'.$n,80); return $n;}

/* Yeni tablolar ve sütunlar yoksa oluşturur. Var olan veriye dokunmaz. */
function migrate($db){
  $db->exec("CREATE TABLE IF NOT EXISTS settings(k VARCHAR(40) PRIMARY KEY, v VARCHAR(255) NOT NULL) CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS prints(
    id INT AUTO_INCREMENT PRIMARY KEY, printed_on DATE NOT NULL, title VARCHAR(150) NOT NULL, qty INT NOT NULL DEFAULT 1,
    hours DECIMAL(6,2) NOT NULL DEFAULT 0, material_cost DECIMAL(10,2) NOT NULL DEFAULT 0, power_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    extra_cost DECIMAL(10,2) NOT NULL DEFAULT 0, sale_price DECIMAL(10,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'ok',
    deducted TINYINT(1) NOT NULL DEFAULT 0, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(printed_on)) CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS print_materials(
    id INT AUTO_INCREMENT PRIMARY KEY, print_id INT NOT NULL, material_id INT NULL, name VARCHAR(150) NOT NULL, color VARCHAR(60),
    hex VARCHAR(7), unit VARCHAR(20) NOT NULL DEFAULT 'g', amount DECIMAL(12,2) NOT NULL DEFAULT 0, cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX(print_id)) CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS sales(
    id INT AUTO_INCREMENT PRIMARY KEY, sold_on DATE NOT NULL, print_id INT NULL, title VARCHAR(150) NOT NULL, qty INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0, unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0, fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping DECIMAL(10,2) NOT NULL DEFAULT 0, channel VARCHAR(30) NOT NULL DEFAULT 'Diğer', customer VARCHAR(120), notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(sold_on), INDEX(print_id)) CHARSET=utf8mb4");
  $cols=$db->query("SHOW COLUMNS FROM materials")->fetchAll(PDO::FETCH_COLUMN);
  foreach(['pack_price'=>"DECIMAL(10,2) NOT NULL DEFAULT 0",'pack_size'=>"DECIMAL(12,2) NOT NULL DEFAULT 1000",'hex'=>"VARCHAR(7) NULL"] as $c=>$def)
    if(!in_array($c,$cols,true)) $db->exec("ALTER TABLE materials ADD COLUMN $c $def");
}

$ok=!empty($_SESSION['ok']); $S=[];
if($ok){ migrate($db); $S=$db->query("SELECT k,v FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR); }
$watt=num($S['printer_watt']??0); $kwh=num($S['kwh_price']??0);

/* ---------- Satışları Excel için CSV olarak indir ---------- */
if($ok && ($_GET['csv']??'')==='satis'){
  [$w,$pa]=per('sold_on'); $r=$db->prepare("SELECT * FROM sales WHERE $w ORDER BY sold_on, id"); $r->execute($pa);
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="satislar-'.$ay.'.csv"');
  $o=fopen('php://output','w'); fwrite($o,"\xEF\xBB\xBF");
  $c=fn($s)=>preg_match('/^[=+\-@\t\r]/',(string)$s) ? "'".$s : (string)$s;   // Excel formül enjeksiyonunu önler
  $n=fn($x)=>number_format((float)$x,2,',','');
  fputcsv($o,['Tarih','Ürün','Adet','Birim fiyat','Tutar','Komisyon','Kargo','Net','Maliyet','Kâr','Kanal','Müşteri','Not'],';','"','\\');
  foreach($r as $s){ $tut=$s['qty']*$s['unit_price']; $net=$tut-$s['fee']-$s['shipping']; $mal=$s['qty']*$s['unit_cost'];
    fputcsv($o,[day($s['sold_on']),$c($s['title']),$s['qty'],$n($s['unit_price']),$n($tut),$n($s['fee']),$n($s['shipping']),$n($net),$n($mal),$n($net-$mal),$s['channel'],$c($s['customer']),$c($s['notes'])],';','"','\\'); }
  exit;
}

/* ---------- Form işlemleri ---------- */
if($ok && $_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['login'])){
  if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))) exit('Geçersiz istek.');
  $a=$_POST['a']??''; $id=(int)($_POST['id']??0); $P=fn($k)=>trim((string)($_POST[$k]??''));

  // Ürünler
  if($a==='padd'){ $u=$P('shopier_url'); if($u!==''&&!preg_match('#^https://#',$u))$u='';
    $db->prepare("INSERT INTO products(name,category,price,description,image,shopier_url) VALUES(?,?,?,?,?,?)")->execute([$P('name'),$P('category')?:'Genel',num($P('price')),$P('description'),saveImg($_FILES['image']??null),$u]); back('Ürün eklendi.'); }
  if($a==='ptog'){ $db->prepare("UPDATE products SET active=1-active WHERE id=?")->execute([$id]); back(); }
  if($a==='pdel'){ $r=$db->prepare("SELECT image FROM products WHERE id=?");$r->execute([$id]);$im=$r->fetchColumn(); if($im)@unlink(__DIR__.'/uploads/'.basename($im)); $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]); back('Ürün silindi.'); }

  // Stok
  if($a==='madd'||$a==='medit'){
    if($P('name')==='') back('Malzeme adı boş olamaz.');
    $v=[$P('name'),$P('kind')?:'Filament',$P('color'),hexok($P('hex')),$P('unit')?:'g',max(0,num($P('qty'))),max(0,num($P('min_qty'))),max(0,num($P('pack_price'))),max(0,num($P('pack_size')))];
    if($a==='madd'){ $db->prepare("INSERT INTO materials(name,kind,color,hex,unit,qty,min_qty,pack_price,pack_size) VALUES(?,?,?,?,?,?,?,?,?)")->execute($v); back('Malzeme eklendi.'); }
    $v[]=$id; $db->prepare("UPDATE materials SET name=?,kind=?,color=?,hex=?,unit=?,qty=?,min_qty=?,pack_price=?,pack_size=? WHERE id=?")->execute($v); back('Malzeme güncellendi.');
  }
  if($a==='madj'){ $db->prepare("UPDATE materials SET qty=GREATEST(0,qty+?) WHERE id=?")->execute([num($P('delta')),$id]); back('Stok güncellendi.'); }
  if($a==='mpack'){ $db->prepare("UPDATE materials SET qty=qty+pack_size WHERE id=? AND pack_size>0")->execute([$id]); back('1 makara / paket eklendi.'); }
  if($a==='mdel'){ $db->prepare("DELETE FROM materials WHERE id=?")->execute([$id]); back('Malzeme silindi. Geçmiş baskılardaki kayıtları korunur.'); }

  // Ayarlar
  if($a==='set'){ $st=$db->prepare("REPLACE INTO settings(k,v) VALUES(?,?)");
    foreach(['kwh_price','printer_watt'] as $k) $st->execute([$k,(string)max(0,num($P($k)))]); back('Ayarlar kaydedildi.'); }

  // Baskılar
  if($a==='badd'){
    if($P('title')==='') back('Baskı adı boş olamaz.');
    $rows=[]; $mc=0; $amts=(array)($_POST['amt']??[]); $get=$db->prepare("SELECT * FROM materials WHERE id=?");
    foreach((array)($_POST['mat']??[]) as $i=>$mid){ $mid=(int)$mid; $amt=num($amts[$i]??''); if(!$mid||$amt<=0) continue;
      $get->execute([$mid]); $m=$get->fetch(); if(!$m) continue;
      $cost=round(ucost($m)*$amt,2); $mc+=$cost; $rows[]=[$mid,$m['name'],$m['color'],$m['hex'],$m['unit'],$amt,$cost]; }
    $hours=max(0,num($P('hours'))); $pc=round($hours*$watt/1000*$kwh,2);
    $st=array_key_exists($P('status'),STATUS)?$P('status'):'ok'; $ded=(!empty($_POST['deduct']) && $rows)?1:0;
    $db->beginTransaction();
    $db->prepare("INSERT INTO prints(printed_on,title,qty,hours,material_cost,power_cost,extra_cost,sale_price,status,deducted,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([dt($P('printed_on')),$P('title'),max(1,(int)$P('qty')),$hours,$mc,$pc,max(0,num($P('extra_cost'))),max(0,num($P('sale_price'))),$st,$ded,$P('notes')]);
    $pid=(int)$db->lastInsertId();
    $ins=$db->prepare("INSERT INTO print_materials(print_id,material_id,name,color,hex,unit,amount,cost) VALUES(?,?,?,?,?,?,?,?)");
    $dec=$db->prepare("UPDATE materials SET qty=GREATEST(0,qty-?) WHERE id=?");
    foreach($rows as $r){ $ins->execute(array_merge([$pid],$r)); if($ded) $dec->execute([$r[5],$r[0]]); }
    $db->commit();
    back('Baskı kaydedildi.'.($ded?' Kullanılan malzeme stoktan düşüldü.':''));
  }
  if($a==='bedit'){
    $r=$db->prepare("SELECT * FROM prints WHERE id=?"); $r->execute([$id]); $old=$r->fetch(); if(!$old) back();
    if($P('title')==='') back('Baskı adı boş olamaz.');
    $hours=max(0,num($P('hours'))); $pc=abs($hours-(float)$old['hours'])<0.001 ? (float)$old['power_cost'] : round($hours*$watt/1000*$kwh,2);
    $st=array_key_exists($P('status'),STATUS)?$P('status'):'ok';
    $db->prepare("UPDATE prints SET printed_on=?,title=?,qty=?,hours=?,power_cost=?,extra_cost=?,sale_price=?,status=?,notes=? WHERE id=?")
       ->execute([dt($P('printed_on')),$P('title'),max(1,(int)$P('qty')),$hours,$pc,max(0,num($P('extra_cost'))),max(0,num($P('sale_price'))),$st,$P('notes'),$id]);
    back('Baskı güncellendi.');
  }
  if($a==='bdel'){
    $r=$db->prepare("SELECT deducted FROM prints WHERE id=?"); $r->execute([$id]); $ded=$r->fetchColumn(); if($ded===false) back();
    $db->beginTransaction();
    if($ded){ $r=$db->prepare("SELECT material_id,amount FROM print_materials WHERE print_id=? AND material_id IS NOT NULL"); $r->execute([$id]);
      $inc=$db->prepare("UPDATE materials SET qty=qty+? WHERE id=?"); foreach($r->fetchAll() as $x) $inc->execute([$x['amount'],$x['material_id']]); }
    $db->prepare("DELETE FROM print_materials WHERE print_id=?")->execute([$id]);
    $db->prepare("UPDATE sales SET print_id=NULL WHERE print_id=?")->execute([$id]);
    $db->prepare("DELETE FROM prints WHERE id=?")->execute([$id]);
    $db->commit();
    back('Baskı silindi.'.($ded?' Kullanılan malzeme stoğa geri eklendi.':''));
  }

  // Satışlar
  if($a==='sadd'){
    $pid=(int)$P('print_id'); $pr=null;
    if($pid){ $r=$db->prepare("SELECT * FROM prints WHERE id=?"); $r->execute([$pid]); $pr=$r->fetch()?:null; if(!$pr) $pid=0; }
    $title=$P('title'); if($title==='' && $pr) $title=$pr['title']; if($title==='') back('Ürün adı boş olamaz.');
    $uc=$P('unit_cost')==='' ? ($pr?round(pcost($pr),2):0) : max(0,num($P('unit_cost')));
    $ch=in_array($P('channel'),CHANNELS,true)?$P('channel'):'Diğer';
    $db->prepare("INSERT INTO sales(sold_on,print_id,title,qty,unit_price,unit_cost,fee,shipping,channel,customer,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([dt($P('sold_on')),$pid?:null,$title,max(1,(int)$P('qty')),max(0,num($P('unit_price'))),$uc,max(0,num($P('fee'))),max(0,num($P('shipping'))),$ch,$P('customer'),$P('notes')]);
    back('Satış kaydedildi.');
  }
  if($a==='sdel'){ $db->prepare("DELETE FROM sales WHERE id=?")->execute([$id]); back('Satış silindi.'); }
  back();
}

/* ---------- Sayfa verileri ---------- */
if($ok){
  $mats=$db->query("SELECT * FROM materials ORDER BY (qty<=min_qty) DESC, kind, name")->fetchAll();
  $matOpts='<option value="">Malzeme seçin</option>';
  foreach($mats as $m) $matOpts.='<option value="'.$m['id'].'" data-uc="'.ucost($m).'" data-unit="'.h($m['unit']).'">'.h($m['name']).($m['color']!==''&&$m['color']!==null?' · '.h($m['color']):'').' (kalan '.q($m['qty']).' '.h($m['unit']).')</option>';
  $sold=$db->query("SELECT print_id, SUM(qty) FROM sales WHERE print_id IS NOT NULL GROUP BY print_id")->fetchAll(PDO::FETCH_KEY_PAIR);
}
$today=date('Y-m-d');
$C=$ok?'<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">':'';
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Atölye paneli</title><link rel="stylesheet" href="style.css?v=3">
<link rel="manifest" href="admin-manifest.json"><meta name="theme-color" content="#0b7a75">
<link rel="icon" type="image/png" href="app-icon.php?s=192"><link rel="apple-touch-icon" href="app-icon.php?s=180">
<meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="Atölye">
</head><body><main class="wrap" style="padding:20px 20px 60px">
<?php if(!$ok): ?><h1>Yönetim girişi</h1><?php if($msg)echo '<p class="msg">'.h($msg).'</p>'; ?>
<form method="post" class="row" style="max-width:360px;grid-template-columns:1fr"><input type="password" name="pass" placeholder="Şifre" required autofocus autocomplete="current-password">
<label class="ck"><input type="checkbox" name="keep" value="1" checked> Bu cihazda açık kalsın (<?=KEEP_DAYS?> gün)</label><button name="login" value="1">Giriş yap</button></form>
<?php else: ?>
<h1>Atölye paneli</h1>
<nav class="tabs"><a href="?t=m"<?=$t==='m'?' class="on"':''?>>Stok</a><a href="?t=b"<?=$t==='b'?' class="on"':''?>>Baskılar</a><a href="?t=s"<?=$t==='s'?' class="on"':''?>>Satışlar</a><a href="?t=p"<?=$t==='p'?' class="on"':''?>>Ürünler</a><span class="sp"></span><a href="./">Siteyi gör</a><a href="?out=1">Çıkış yap</a></nav>
<?php if($msg)echo '<p class="msg">'.h($msg).'</p>'; ?>

<?php if($t==='b'||$t==='s'): ?>
<form method="get" class="per"><input type="hidden" name="t" value="<?=$t?>"><label>Dönem <input type="month" name="ay" value="<?=$ay==='hepsi'?'':h($ay)?>"></label><button class="alt">Göster</button><a href="?t=<?=$t?>&amp;ay=hepsi">Tüm zamanlar</a><a href="?t=<?=$t?>">Bu ay</a></form>
<?php endif; ?>

<?php if($t==='m'): /* ================= STOK ================= */
  $low=0; $val=0; foreach($mats as $m){ if($m['qty']<=$m['min_qty']) $low++; $val+=$m['qty']*ucost($m); } ?>
<div class="cards"><div><small>Malzeme</small><b><?=count($mats)?></b></div><div><small>Azalan</small><b class="<?=$low?'neg':''?>"><?=$low?></b></div><div><small>Stok değeri</small><b><?=tl($val)?></b></div></div>
<details class="add"<?=$mats?'':' open'?>><summary>+ Yeni malzeme ekle</summary>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="madd">
<label class="lbl">Ad<input name="name" placeholder="örn. Elegoo PLA+ Siyah" required></label>
<label class="lbl">Tür<input name="kind" value="Filament" list="kinds"></label>
<label class="lbl">Renk adı<input name="color" placeholder="Siyah"></label>
<label class="lbl">Renk örneği<input type="color" name="hex" value="#9aa3ab"></label>
<label class="lbl">Birim<input name="unit" value="g"></label>
<label class="lbl">Mevcut miktar<input name="qty" inputmode="decimal" placeholder="1000" required></label>
<label class="lbl">Uyarı seviyesi<input name="min_qty" inputmode="decimal" placeholder="200"></label>
<label class="lbl">Makara / paket fiyatı (₺)<input name="pack_price" inputmode="decimal" placeholder="650"></label>
<label class="lbl">Makara / paket miktarı<input name="pack_size" inputmode="decimal" value="1000"></label>
<button>Malzeme ekle</button></form></details>
<datalist id="kinds"><option value="Filament"><option value="Reçine"><option value="Vida / Somun"><option value="Mıknatıs"><option value="Ambalaj"><option value="Boya"><option value="Diğer"></datalist>
<div class="x"><table><tr><th>Malzeme</th><th>Kalan</th><th>Birim maliyet</th><th>Stok ekle / düş</th><th></th></tr>
<?php foreach($mats as $m): $lo=$m['qty']<=$m['min_qty']; $pct=$m['pack_size']>0?min(100,$m['qty']/$m['pack_size']*100):0; ?>
<tr><td><?=sw($m['hex'])?><b><?=h($m['name'])?></b><br><small><?=h($m['kind'])?><?=$m['color']?' · '.h($m['color']):''?></small></td>
<td class="nowrap<?=$lo?' low':''?>"><?=q($m['qty'])?> <?=h($m['unit'])?><?=$lo?' · azaldı':''?><span class="bar"><i style="width:<?=round($pct)?>%"></i></span><small>uyarı: <?=q($m['min_qty'])?></small></td>
<td class="nowrap"><?=ulabel($m)?></td>
<td><form method="post" class="inl"><?=$C?><input type="hidden" name="a" value="madj"><input type="hidden" name="id" value="<?=$m['id']?>"><input name="delta" inputmode="decimal" placeholder="+500 / -40" required><button class="alt">Kaydet</button></form>
<?php if($m['pack_size']>0): ?><form method="post"><?=$C?><input type="hidden" name="id" value="<?=$m['id']?>"><button class="lnk" name="a" value="mpack">+1 makara (<?=q($m['pack_size'])?> <?=h($m['unit'])?>)</button></form><?php endif; ?></td>
<td class="act"><details class="ed"><summary>Düzenle</summary><form method="post" class="row"><?=$C?><input type="hidden" name="a" value="medit"><input type="hidden" name="id" value="<?=$m['id']?>">
<label class="lbl">Ad<input name="name" value="<?=h($m['name'])?>" required></label><label class="lbl">Tür<input name="kind" value="<?=h($m['kind'])?>" list="kinds"></label>
<label class="lbl">Renk adı<input name="color" value="<?=h($m['color'])?>"></label><label class="lbl">Renk örneği<input type="color" name="hex" value="<?=h($m['hex']?:'#9aa3ab')?>"></label>
<label class="lbl">Birim<input name="unit" value="<?=h($m['unit'])?>"></label><label class="lbl">Kalan (tartı)<input name="qty" inputmode="decimal" value="<?=h($m['qty']+0)?>"></label>
<label class="lbl">Uyarı seviyesi<input name="min_qty" inputmode="decimal" value="<?=h($m['min_qty']+0)?>"></label>
<label class="lbl">Makara fiyatı (₺)<input name="pack_price" inputmode="decimal" value="<?=h($m['pack_price']+0)?>"></label><label class="lbl">Makara miktarı<input name="pack_size" inputmode="decimal" value="<?=h($m['pack_size']+0)?>"></label>
<button>Kaydet</button></form></details>
<form method="post"><?=$C?><input type="hidden" name="id" value="<?=$m['id']?>"><button class="lnk neg" name="a" value="mdel" onclick="return confirm('Bu malzeme silinsin mi?')">Sil</button></form></td></tr>
<?php endforeach; if(!$mats): ?><tr><td colspan="5" class="empty">Henüz malzeme yok. Yukarıdan ilk makaranızı ekleyin.</td></tr><?php endif; ?></table></div>

<?php elseif($t==='b'): /* ================= BASKILAR ================= */
  [$w,$pa]=per('printed_on'); [$w2,$pa2]=per('p.printed_on');
  $r=$db->prepare("SELECT COUNT(*) n, COALESCE(SUM(material_cost+power_cost+extra_cost),0) c, COALESCE(SUM(CASE WHEN status='fire' THEN material_cost+power_cost+extra_cost ELSE 0 END),0) f, COALESCE(SUM(hours),0) h FROM prints WHERE $w"); $r->execute($pa); $sum=$r->fetch();
  $r=$db->prepare("SELECT COALESCE(SUM(pm.amount),0) FROM print_materials pm JOIN prints p ON p.id=pm.print_id WHERE pm.unit='g' AND $w2"); $r->execute($pa2); $grams=$r->fetchColumn();
  $r=$db->prepare("SELECT * FROM prints WHERE $w ORDER BY printed_on DESC, id DESC LIMIT 300"); $r->execute($pa); $prints=$r->fetchAll();
  $pm=[]; if($prints){ foreach($db->query("SELECT * FROM print_materials WHERE print_id IN (".implode(',',array_map(fn($p)=>(int)$p['id'],$prints)).") ORDER BY id") as $x) $pm[$x['print_id']][]=$x; } ?>
<div class="cards"><div><small><?=h(plabel())?> · baskı</small><b><?=$sum['n']?></b></div><div><small>Toplam maliyet</small><b><?=tl($sum['c'])?></b></div><div><small>Kullanılan filament</small><b><?=q($grams)?> g</b></div><div><small>Baskı süresi</small><b><?=q($sum['h'])?> sa</b></div><div><small>Fire maliyeti</small><b class="<?=$sum['f']>0?'neg':''?>"><?=tl($sum['f'])?></b></div></div>
<details class="add" open><summary>+ Yeni baskı kaydet</summary>
<form method="post" class="row" id="bform" data-watt="<?=$watt?>" data-kwh="<?=$kwh?>"><?=$C?><input type="hidden" name="a" value="badd">
<label class="lbl">Tarih<input type="date" name="printed_on" value="<?=$today?>"></label>
<label class="lbl full2">Ne bastınız?<input name="title" placeholder="örn. İsimli anahtarlık – Elif" required></label>
<label class="lbl">Adet<input name="qty" type="number" min="1" value="1"></label>
<label class="lbl">Baskı süresi (saat)<input name="hours" inputmode="decimal" placeholder="2,5"></label>
<?php for($i=0;$i<3;$i++): ?><div class="mrow"><label class="lbl">Malzeme <?=$i+1?><select name="mat[]"><?=$matOpts?></select></label><label class="lbl">Kullanılan<input name="amt[]" inputmode="decimal" placeholder="0"></label><span class="u"></span></div><?php endfor; ?>
<label class="lbl">Diğer maliyet (₺)<input name="extra_cost" inputmode="decimal" placeholder="ambalaj, boya, aparat"></label>
<label class="lbl">Satış fiyatı / adet (₺)<input name="sale_price" inputmode="decimal" placeholder="0"></label>
<label class="lbl">Durum<select name="status"><?php foreach(STATUS as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?></select></label>
<label class="lbl full">Not<textarea name="notes" rows="2" placeholder="Katman yüksekliği, doluluk, müşteri isteği..."></textarea></label>
<div class="calc" id="bcalc"></div>
<label class="ck full"><input type="checkbox" name="deduct" value="1" checked> Kullanılan malzemeyi stoktan düş</label>
<button>Baskıyı kaydet</button></form></details>
<details class="add"><summary>Elektrik ayarları <?=($watt&&$kwh)?'· '.q($watt).' W, '.q($kwh).' ₺/kWh':'· tanımlı değil'?></summary>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="set">
<label class="lbl">Elektrik birim fiyatı (₺/kWh)<input name="kwh_price" inputmode="decimal" value="<?=h($kwh?:'')?>" placeholder="faturanızdaki birim fiyat"></label>
<label class="lbl">Yazıcının ortalama gücü (W)<input name="printer_watt" inputmode="decimal" value="<?=h($watt?:'')?>" placeholder="örn. 150"></label>
<p class="full hint">Baskı süresiyle çarpılarak her baskıya elektrik maliyeti eklenir. Ortalama güç, yazıcının etiketindeki en yüksek güçten düşüktür; bir prizli wattmetreyle ölçebilirsiniz.</p>
<button>Kaydet</button></form></details>
<div class="x"><table><tr><th>Tarih</th><th>Baskı</th><th>Malzeme</th><th>Adet</th><th>Maliyet</th><th>Satış / adet</th><th>Kâr / adet</th><th></th></tr>
<?php foreach($prints as $p): $u=pcost($p); $tot=$u*max(1,(int)$p['qty']); $sd=(int)($sold[$p['id']]??0); ?>
<tr><td class="nowrap"><?=day($p['printed_on'])?></td>
<td><b><?=h($p['title'])?></b><?=$p['status']!=='ok'?'<span class="badge'.($p['status']==='test'?' t':'').'">'.h(STATUS[$p['status']]??$p['status']).'</span>':''?><?=$p['notes']?'<br><small>'.h($p['notes']).'</small>':''?></td>
<td><?php foreach($pm[$p['id']]??[] as $x): ?><div class="nowrap"><?=sw($x['hex'])?><?=h($x['name'])?> <small><?=q($x['amount'])?> <?=h($x['unit'])?></small></div><?php endforeach; ?><?=$p['hours']>0?'<small>'.q($p['hours']).' saat</small>':''?></td>
<td class="nowrap"><?=(int)$p['qty']?><?=$sd?'<br><small>'.$sd.' satıldı</small>':''?></td>
<td class="nowrap"><?=tl($tot)?><br><small>adet: <?=tl($u)?></small></td>
<td class="nowrap"><?=$p['sale_price']>0?tl($p['sale_price']):'—'?></td>
<td class="nowrap"><?php if($p['sale_price']>0): $k=$p['sale_price']-$u; ?><b class="<?=$k<0?'neg':'pos'?>"><?=tl($k)?></b><?php else: ?>—<?php endif; ?></td>
<td class="act"><details class="ed"><summary>Düzenle</summary><form method="post" class="row"><?=$C?><input type="hidden" name="a" value="bedit"><input type="hidden" name="id" value="<?=$p['id']?>">
<label class="lbl">Tarih<input type="date" name="printed_on" value="<?=h($p['printed_on'])?>"></label><label class="lbl">Ad<input name="title" value="<?=h($p['title'])?>" required></label>
<label class="lbl">Adet<input name="qty" type="number" min="1" value="<?=(int)$p['qty']?>"></label><label class="lbl">Süre (saat)<input name="hours" inputmode="decimal" value="<?=h($p['hours']+0)?>"></label>
<label class="lbl">Diğer maliyet (₺)<input name="extra_cost" inputmode="decimal" value="<?=h($p['extra_cost']+0)?>"></label><label class="lbl">Satış fiyatı / adet<input name="sale_price" inputmode="decimal" value="<?=h($p['sale_price']+0)?>"></label>
<label class="lbl">Durum<select name="status"><?php foreach(STATUS as $k=>$v): ?><option value="<?=$k?>"<?=$p['status']===$k?' selected':''?>><?=$v?></option><?php endforeach; ?></select></label>
<label class="lbl full">Not<textarea name="notes" rows="2"><?=h($p['notes'])?></textarea></label>
<p class="full hint">Kullanılan malzemeyi değiştirmek için baskıyı silip yeniden kaydedin.</p><button>Kaydet</button></form></details>
<form method="post"><?=$C?><input type="hidden" name="id" value="<?=$p['id']?>"><button class="lnk neg" name="a" value="bdel" onclick="return confirm('Bu baskı silinsin mi?<?=$p['deducted']?' Kullanılan malzeme stoğa geri eklenir.':''?>')">Sil</button></form></td></tr>
<?php endforeach; if(!$prints): ?><tr><td colspan="8" class="empty"><?=h(plabel())?> için kayıtlı baskı yok.</td></tr><?php endif; ?></table></div>

<?php elseif($t==='s'): /* ================= SATIŞLAR ================= */
  [$w,$pa]=per('sold_on');
  $r=$db->prepare("SELECT COUNT(*) n, COALESCE(SUM(qty),0) adet, COALESCE(SUM(qty*unit_price),0) ciro, COALESCE(SUM(fee+shipping),0) kes, COALESCE(SUM(qty*unit_cost),0) mal FROM sales WHERE $w"); $r->execute($pa); $sum=$r->fetch();
  $net=$sum['ciro']-$sum['kes']; $kar=$net-$sum['mal'];
  $r=$db->prepare("SELECT channel, COUNT(*) n, SUM(qty*unit_price) c FROM sales WHERE $w GROUP BY channel ORDER BY c DESC"); $r->execute($pa); $chs=$r->fetchAll();
  $r=$db->prepare("SELECT * FROM sales WHERE $w ORDER BY sold_on DESC, id DESC LIMIT 300"); $r->execute($pa); $sales=$r->fetchAll();
  $plist=$db->query("SELECT * FROM prints WHERE status='ok' ORDER BY printed_on DESC, id DESC LIMIT 200")->fetchAll(); ?>
<div class="cards"><div><small><?=h(plabel())?> · satış</small><b><?=$sum['n']?> <small>(<?=$sum['adet']?> adet)</small></b></div><div><small>Ciro</small><b><?=tl($sum['ciro'])?></b></div><div><small>Komisyon + kargo</small><b><?=tl($sum['kes'])?></b></div><div><small>Net</small><b><?=tl($net)?></b></div><div><small>Kâr</small><b class="<?=$kar<0?'neg':'pos'?>"><?=tl($kar)?></b></div></div>
<?php if($chs): ?><p class="chs"><?php foreach($chs as $c): ?><span><b><?=h($c['channel'])?></b> <?=tl($c['c'])?> <small>(<?=$c['n']?>)</small></span><?php endforeach; ?></p><?php endif; ?>
<details class="add" open><summary>+ Yeni satış kaydet</summary>
<form method="post" class="row" id="sform"><?=$C?><input type="hidden" name="a" value="sadd">
<label class="lbl">Tarih<input type="date" name="sold_on" value="<?=$today?>"></label>
<label class="lbl full2">Baskıdan seç (isteğe bağlı)<select name="print_id"><option value="">Baskı seçmeden gir</option><?php foreach($plist as $p): $left=(int)$p['qty']-(int)($sold[$p['id']]??0); ?><option value="<?=$p['id']?>" data-title="<?=h($p['title'])?>" data-price="<?=$p['sale_price']+0?>" data-cost="<?=round(pcost($p),2)?>"><?=day($p['printed_on'])?> · <?=h($p['title'])?><?=$left>0?' (elde '.$left.')':' (hepsi satıldı)'?></option><?php endforeach; ?></select></label>
<label class="lbl full2">Ürün<input name="title" placeholder="Ne sattınız?"></label>
<label class="lbl">Adet<input name="qty" type="number" min="1" value="1"></label>
<label class="lbl">Birim fiyat (₺)<input name="unit_price" inputmode="decimal" required></label>
<label class="lbl">Birim maliyet (₺)<input name="unit_cost" inputmode="decimal" placeholder="baskıdan gelir"></label>
<label class="lbl">Kanal<select name="channel"><?php foreach(CHANNELS as $c): ?><option><?=$c?></option><?php endforeach; ?></select></label>
<label class="lbl">Komisyon (₺)<input name="fee" inputmode="decimal" placeholder="0"></label>
<label class="lbl">Kargo – sizin ödediğiniz (₺)<input name="shipping" inputmode="decimal" placeholder="0"></label>
<label class="lbl">Müşteri (isteğe bağlı)<input name="customer" placeholder="ad ya da kullanıcı adı"></label>
<label class="lbl full">Not<textarea name="notes" rows="2"></textarea></label>
<button>Satışı kaydet</button></form></details>
<p><a href="?t=s&amp;ay=<?=h($ay)?>&amp;csv=satis">Excel'e aktar (CSV) · <?=h(plabel())?></a></p>
<div class="x"><table><tr><th>Tarih</th><th>Ürün</th><th>Kanal</th><th>Adet</th><th>Tutar</th><th>Kesinti</th><th>Net</th><th>Kâr</th><th></th></tr>
<?php foreach($sales as $s): $tut=$s['qty']*$s['unit_price']; $kes=$s['fee']+$s['shipping']; $nt=$tut-$kes; $k=$nt-$s['qty']*$s['unit_cost']; ?>
<tr><td class="nowrap"><?=day($s['sold_on'])?></td><td><b><?=h($s['title'])?></b><?=$s['customer']?'<br><small>'.h($s['customer']).'</small>':''?><?=$s['notes']?'<br><small>'.h($s['notes']).'</small>':''?></td>
<td><?=h($s['channel'])?></td><td><?=(int)$s['qty']?><?=$s['qty']>1?'<br><small>× '.tl($s['unit_price']).'</small>':''?></td><td class="nowrap"><?=tl($tut)?></td><td class="nowrap"><?=$kes>0?tl($kes):'—'?></td><td class="nowrap"><?=tl($nt)?></td>
<td class="nowrap"><b class="<?=$k<0?'neg':'pos'?>"><?=tl($k)?></b><?=$s['unit_cost']<=0?'<br><small>maliyet girilmedi</small>':''?></td>
<td class="act"><form method="post"><?=$C?><input type="hidden" name="id" value="<?=$s['id']?>"><button class="lnk neg" name="a" value="sdel" onclick="return confirm('Bu satış silinsin mi?')">Sil</button></form></td></tr>
<?php endforeach; if(!$sales): ?><tr><td colspan="9" class="empty"><?=h(plabel())?> için kayıtlı satış yok.</td></tr><?php endif; ?></table></div>

<?php else: /* ================= ÜRÜNLER (sitede görünenler) ================= */ ?>
<form method="post" enctype="multipart/form-data" class="row"><?=$C?><input type="hidden" name="a" value="padd">
<input name="name" placeholder="Ürün adı" required><input name="category" placeholder="Kategori"><input name="price" inputmode="decimal" placeholder="Fiyat (₺)" required>
<input name="shopier_url" placeholder="Shopier ürün linki (https://...)"><input type="file" name="image" accept="image/jpeg,image/png,image/webp">
<textarea name="description" placeholder="Kısa açıklama" rows="2"></textarea><button>Ürün ekle</button></form>
<div class="x"><table><tr><th>Ürün</th><th>Fiyat</th><th>Durum</th><th></th></tr>
<?php foreach($db->query("SELECT * FROM products ORDER BY id DESC") as $p): ?><tr><td><?=h($p['name'])?><br><small><?=h($p['category'])?></small></td><td><?=tl($p['price'])?></td><td><?=$p['active']?'Yayında':'Gizli'?></td>
<td><form method="post" style="display:inline"><?=$C?><input type="hidden" name="id" value="<?=$p['id']?>"><button class="alt" name="a" value="ptog"><?=$p['active']?'Gizle':'Yayınla'?></button>
<button class="del" name="a" value="pdel" onclick="return confirm('Bu ürün silinsin mi?')">Sil</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; endif; ?></main>
<script>
(function(){
  function n(v){ v=String(v||'').trim(); if(v.indexOf(',')>-1) v=v.replace(/[.\s]/g,'').replace(',','.'); return parseFloat(v)||0; }
  function tl(x){ return x.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺'; }
  // Telefona kurulabilen uygulama: internet yokken uyarı sayfası gösterir
  if('serviceWorker' in navigator) navigator.serviceWorker.register('admin-sw.js',{scope:'admin.php'}).catch(function(){});
  // Telefonda tablolar kart olarak görünür; her hücreye sütun başlığını etiket olarak ekler
  document.querySelectorAll('.x table').forEach(function(tb){
    var hr=tb.querySelector('tr'); if(!hr||!hr.querySelector('th')) return; hr.classList.add('h');
    var hs=[].map.call(hr.children,function(c){ return c.textContent.trim(); });
    [].forEach.call(tb.rows,function(r){ if(r===hr) return; [].forEach.call(r.cells,function(td,i){ if(!td.colSpan||td.colSpan<2){ if(hs[i]) td.setAttribute('data-l',hs[i]); } }); });
  });
  // Baskı formu: maliyeti anında hesaplar
  var f=document.getElementById('bform');
  if(f){
    var E=function(k){ return f.elements.namedItem(k); }, out=document.getElementById('bcalc'), W=+f.dataset.watt||0, K=+f.dataset.kwh||0;
    var calc=function(){
      var mc=0;
      f.querySelectorAll('.mrow').forEach(function(r){ var o=r.querySelector('select').selectedOptions[0], u=r.querySelector('.u');
        if(o&&o.value){ mc+=(+o.dataset.uc||0)*n(r.querySelector('input').value); u.textContent=o.dataset.unit; } else u.textContent=''; });
      var pc=n(E('hours').value)*W/1000*K, ex=n(E('extra_cost').value), q=Math.max(1,parseInt(E('qty').value,10)||1), tot=mc+pc+ex, per=tot/q, sp=n(E('sale_price').value);
      out.innerHTML='Malzeme <b>'+tl(mc)+'</b> · Elektrik <b>'+tl(pc)+'</b>'+(W&&K?'':' <small>(ayar yok)</small>')+' · Diğer <b>'+tl(ex)+'</b><br>Toplam <b>'+tl(tot)+'</b> · Adet başı <b>'+tl(per)+'</b>'+(sp>0?' · Adet başı kâr <b class="'+(sp-per<0?'neg':'pos')+'">'+tl(sp-per)+'</b>':'');
    };
    f.addEventListener('input',calc); f.addEventListener('change',calc); calc();
  }
  // Satış formu: baskı seçilince ad, fiyat ve maliyeti doldurur
  var s=document.getElementById('sform');
  if(s){
    var G=function(k){ return s.elements.namedItem(k); };
    G('print_id').addEventListener('change',function(){ var o=this.selectedOptions[0]; if(!o||!o.value) return;
      G('title').value=o.dataset.title; if(+o.dataset.price) G('unit_price').value=o.dataset.price; G('unit_cost').value=o.dataset.cost; });
  }
})();
</script>
</body></html>
