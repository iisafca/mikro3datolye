<?php require 'config.php';
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16));
$msg='';
if(isset($_POST['login'])){ if(!empty($env['ADMIN_PASS']) && hash_equals($env['ADMIN_PASS'],(string)$_POST['pass'])){session_regenerate_id(true);$_SESSION['ok']=1;} else {$msg='Şifre hatalı.';usleep(600000);} }
if(isset($_GET['out'])){session_destroy();header('Location: admin.php');exit;}
function saveImg($f){
  if(!$f||$f['error']!==0)return null; $i=@getimagesize($f['tmp_name']);
  if(!$i||!in_array($i[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP]))return null;
  $s=match($i[2]){IMAGETYPE_JPEG=>imagecreatefromjpeg($f['tmp_name']),IMAGETYPE_PNG=>imagecreatefrompng($f['tmp_name']),default=>imagecreatefromwebp($f['tmp_name'])};
  $k=min(1,1000/max($i[0],$i[1])); $d=imagescale($s,max(1,(int)($i[0]*$k)),max(1,(int)($i[1]*$k)));
  if(!is_dir(__DIR__.'/uploads'))mkdir(__DIR__.'/uploads',0755); $n=bin2hex(random_bytes(8)).'.webp'; imagewebp($d,__DIR__.'/uploads/'.$n,80); return $n;}
if(!empty($_SESSION['ok']) && $_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['login'])){
  if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))) exit('Geçersiz istek.');
  $a=$_POST['a']??'';
  if($a==='padd'){ $u=trim($_POST['shopier_url']); if($u!==''&&!preg_match('#^https://#',$u))$u='';
    $db->prepare("INSERT INTO products(name,category,price,description,image,shopier_url) VALUES(?,?,?,?,?,?)")->execute([trim($_POST['name']),trim($_POST['category'])?:'Genel',(float)str_replace(',','.',$_POST['price']),trim($_POST['description']),saveImg($_FILES['image']??null),$u]); $msg='Ürün eklendi.'; }
  if($a==='ptog')$db->prepare("UPDATE products SET active=1-active WHERE id=?")->execute([(int)$_POST['id']]);
  if($a==='pdel'){ $r=$db->prepare("SELECT image FROM products WHERE id=?");$r->execute([(int)$_POST['id']]);$im=$r->fetchColumn(); if($im)@unlink(__DIR__.'/uploads/'.basename($im)); $db->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]); $msg='Ürün silindi.'; }
  if($a==='madd'){ $db->prepare("INSERT INTO materials(name,kind,color,unit,qty,min_qty) VALUES(?,?,?,?,?,?)")->execute([trim($_POST['name']),trim($_POST['kind']),trim($_POST['color']),trim($_POST['unit'])?:'g',(float)$_POST['qty'],(float)$_POST['min_qty']]); $msg='Malzeme eklendi.'; }
  if($a==='madj')$db->prepare("UPDATE materials SET qty=GREATEST(0,qty+?) WHERE id=?")->execute([(float)$_POST['delta'],(int)$_POST['id']]);
  if($a==='mdel')$db->prepare("DELETE FROM materials WHERE id=?")->execute([(int)$_POST['id']]);
}
$t=$_GET['t']??'m';
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title>Yönetim</title><link rel="stylesheet" href="style.css"></head><body><main class="wrap" style="padding-top:20px">
<?php if(empty($_SESSION['ok'])): ?><h1>Yönetim girişi</h1><?php if($msg)echo '<p class="msg">'.h($msg).'</p>'; ?>
<form method="post" class="row" style="max-width:360px"><input type="password" name="pass" placeholder="Şifre" required autofocus><button name="login" value="1">Giriş yap</button></form>
<?php else: $C='<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; ?>
<h1>Yönetim</h1><p class="tabs"><a href="?t=m">Malzemeler</a><a href="?t=p">Ürünler</a><a href="./">Siteyi gör</a><a href="?out=1">Çıkış yap</a></p>
<?php if($msg)echo '<p class="msg">'.h($msg).'</p>'; ?>
<?php if($t==='p'): ?>
<form method="post" enctype="multipart/form-data" class="row"><?=$C?><input type="hidden" name="a" value="padd">
<input name="name" placeholder="Ürün adı" required><input name="category" placeholder="Kategori"><input name="price" placeholder="Fiyat (₺)" required>
<input name="shopier_url" placeholder="Shopier ürün linki (https://...)"><input type="file" name="image" accept="image/jpeg,image/png,image/webp">
<textarea name="description" placeholder="Kısa açıklama" rows="2"></textarea><button>Ürün ekle</button></form>
<div class="x"><table><tr><th>Ürün</th><th>Fiyat</th><th>Durum</th><th></th></tr>
<?php foreach($db->query("SELECT * FROM products ORDER BY id DESC") as $p): ?><tr><td><?=h($p['name'])?><br><small><?=h($p['category'])?></small></td><td><?=tl($p['price'])?></td><td><?=$p['active']?'Yayında':'Gizli'?></td>
<td><form method="post" style="display:inline"><?=$C?><input type="hidden" name="id" value="<?=$p['id']?>"><button class="alt" name="a" value="ptog"><?=$p['active']?'Gizle':'Yayınla'?></button>
<button class="del" name="a" value="pdel" onclick="return confirm('Bu ürün silinsin mi?')">Sil</button></form></td></tr><?php endforeach; ?></table></div>
<?php else: ?>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="madd"><input name="name" placeholder="Malzeme (örn. PLA+ Siyah)" required>
<input name="kind" placeholder="Tür (Filament, Reçine, Vida...)" value="Filament"><input name="color" placeholder="Renk"><input name="unit" placeholder="Birim" value="g">
<input name="qty" type="number" step="any" placeholder="Mevcut miktar" required><input name="min_qty" type="number" step="any" placeholder="Uyarı seviyesi" required><button>Malzeme ekle</button></form>
<div class="x"><table><tr><th>Malzeme</th><th>Kalan</th><th>Uyarı seviyesi</th><th>Stok ekle / düş</th><th></th></tr>
<?php foreach($db->query("SELECT * FROM materials ORDER BY (qty<=min_qty) DESC, name") as $m): $lo=$m['qty']<=$m['min_qty']; ?>
<tr><td><?=h($m['name'])?><br><small><?=h($m['kind'])?> <?=h($m['color'])?></small></td><td class="<?=$lo?'low':''?>"><?=h($m['qty']+0)?> <?=h($m['unit'])?><?=$lo?' (azaldı)':''?></td><td><?=h($m['min_qty']+0)?></td>
<td><form method="post" style="display:flex;gap:6px"><?=$C?><input type="hidden" name="a" value="madj"><input type="hidden" name="id" value="<?=$m['id']?>"><input name="delta" type="number" step="any" placeholder="+500 veya -40" required><button class="alt">Kaydet</button></form></td>
<td><form method="post"><?=$C?><input type="hidden" name="id" value="<?=$m['id']?>"><button class="del" name="a" value="mdel" onclick="return confirm('Bu malzeme silinsin mi?')">Sil</button></form></td></tr><?php endforeach; ?></table></div>
<?php endif; endif; ?></main></body></html>
