<?php
/* Atölye paneli ek modülü: çok parçalı ürün şablonları, özet ekranı, giderler, alış kayıtları, sipariş takibi,
   giriş koruması ve otomatik e-posta yedeği. admin.php tarafından yüklenir; tek başına bir sayfa değildir.
   admin.php'deki yardımcıları kullanır: num, q, day, dt, sw, ucost, pcost, sstat, per, plabel, back. */
if(!function_exists('h')){ http_response_code(404); exit; }   // doğrudan açılırsa hiçbir şey göstermez
const PAID=['odendi'=>'Ödendi','kapora'=>'Kapora alındı','odenmedi'=>'Ödenmedi'];
const GCATS=['Kargo / ambalaj','Yazıcı parçası / bakım','Platform ücreti','Fatura','Reklam','Diğer'];
const YEDEK_MAIL='info@mikro3datolye.com.tr';

/* ---------- Giriş koruması: aynı adresten 15 dakikada 5 hatalı denemeden sonra giriş kilitlenir ---------- */
function ek_ip(){ return substr((string)($_SERVER['REMOTE_ADDR']??'?'),0,45); }
function ek_blocked($db){
  try{
    $db->exec("CREATE TABLE IF NOT EXISTS login_fails(id INT AUTO_INCREMENT PRIMARY KEY, ip VARCHAR(45) NOT NULL, at INT NOT NULL, INDEX(ip)) CHARSET=utf8mb4");
    $db->prepare("DELETE FROM login_fails WHERE at<?")->execute([time()-900]);
    $r=$db->prepare("SELECT COUNT(*) FROM login_fails WHERE ip=?"); $r->execute([ek_ip()]);
    return (int)$r->fetchColumn()>=5;
  }catch(Throwable $e){ return false; }
}
function ek_login_fail($db){ try{ $db->prepare("INSERT INTO login_fails(ip,at) VALUES(?,?)")->execute([ek_ip(),time()]); }catch(Throwable $e){} }
function ek_login_ok($db){ try{ $db->prepare("DELETE FROM login_fails WHERE ip=?")->execute([ek_ip()]); }catch(Throwable $e){} }

/* ---------- Kurulum: yeni tablolar ve sütunlar (bir kez çalışır) ---------- */
function ek_migrate($db,$S){
  if(($S['ek_v']??'')==='1') return;
  $db->exec("CREATE TABLE IF NOT EXISTS recipes(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, sale_price DECIMAL(10,2) NOT NULL DEFAULT 0, extra_cost DECIMAL(10,2) NOT NULL DEFAULT 0, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS recipe_parts(id INT AUTO_INCREMENT PRIMARY KEY, recipe_id INT NOT NULL, title VARCHAR(100) NOT NULL, material_id INT NULL, amount DECIMAL(12,2) NOT NULL DEFAULT 0, hours DECIMAL(6,2) NOT NULL DEFAULT 0, INDEX(recipe_id)) CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS expenses(id INT AUTO_INCREMENT PRIMARY KEY, spent_on DATE NOT NULL, title VARCHAR(150) NOT NULL, category VARCHAR(60) NOT NULL DEFAULT 'Diğer', amount DECIMAL(10,2) NOT NULL DEFAULT 0, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(spent_on)) CHARSET=utf8mb4");
  $db->exec("CREATE TABLE IF NOT EXISTS purchases(id INT AUTO_INCREMENT PRIMARY KEY, bought_on DATE NOT NULL, material_id INT NULL, name VARCHAR(150) NOT NULL, qty DECIMAL(12,2) NOT NULL DEFAULT 0, price DECIMAL(10,2) NOT NULL DEFAULT 0, notes VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(bought_on)) CHARSET=utf8mb4");
  $add=function($tb,$cols) use($db){ $have=$db->query("SHOW COLUMNS FROM $tb")->fetchAll(PDO::FETCH_COLUMN); foreach($cols as $k=>$d) if(!in_array($k,$have,true)) $db->exec("ALTER TABLE $tb ADD COLUMN $k $d"); };
  $add('sales',['due_on'=>"DATE NULL",'paid'=>"VARCHAR(10) NOT NULL DEFAULT 'odendi'",'deposit'=>"DECIMAL(10,2) NOT NULL DEFAULT 0",'phone'=>"VARCHAR(30) NULL",'recipe_id'=>"INT NULL"]);
  $add('prints',['recipe_id'=>"INT NULL"]);
  $add('print_materials',['part'=>"VARCHAR(100) NULL"]);
  $db->exec("REPLACE INTO settings(k,v) VALUES('ek_v','1')");
}

/* ---------- Ürün şablonları: bir ürün birden fazla parçadan (ayrı baskıdan) oluşur ---------- */
function ek_recipes($db){
  $rs=$db->query("SELECT * FROM recipes ORDER BY name")->fetchAll(); if(!$rs) return [];
  $parts=[];
  foreach($db->query("SELECT rp.*, m.name mname, m.color mcolor, m.hex mhex, m.unit munit, m.qty mqty, m.pack_price, m.pack_size FROM recipe_parts rp LEFT JOIN materials m ON m.id=rp.material_id ORDER BY rp.id") as $p) $parts[$p['recipe_id']][]=$p;
  $out=[]; foreach($rs as $r){ $r['parts']=$parts[$r['id']]??[]; $out[]=$r; } return $out;
}
function ek_one($db,$id){ foreach(ek_recipes($db) as $r) if((int)$r['id']===(int)$id) return $r; return null; }
// Bir parçanın malzeme maliyeti (güncel filament fiyatıyla)
function ek_pcost($p){ return ($p['mname']!==null && (float)$p['pack_size']>0) ? (float)$p['amount']*(float)$p['pack_price']/(float)$p['pack_size'] : 0.0; }
// Şablonun 1 adet maliyeti: tüm parçaların malzemesi + toplam baskı süresinin elektriği + diğer
function ek_rcost($r,$watt,$kwh){
  $mc=0; $h=0; foreach($r['parts'] as $p){ $mc+=ek_pcost($p); $h+=(float)$p['hours']; }
  $pc=$h*$watt/1000*$kwh; $ex=(float)$r['extra_cost'];
  return ['mat'=>$mc,'pow'=>$pc,'hours'=>$h,'extra'=>$ex,'total'=>$mc+$pc+$ex];
}
// Şablondan üretim: tek baskı kaydı açar, her parça için malzeme satırı yazar ve stoktan düşer. Baskı numarasını döndürür.
function ek_produce($db,$r,$n,$date,$watt,$kwh){
  $c=ek_rcost($r,$watt,$kwh); $rows=[];
  foreach($r['parts'] as $p) if($p['mname']!==null && (float)$p['amount']>0) $rows[]=$p;
  $db->prepare("INSERT INTO prints(printed_on,title,qty,hours,material_cost,power_cost,extra_cost,sale_price,status,deducted,notes,recipe_id) VALUES(?,?,?,?,?,?,?,?,'ok',?,?,?)")
     ->execute([$date,$r['name'],$n,round($c['hours']*$n,2),round($c['mat']*$n,2),round($c['pow']*$n,2),round($c['extra']*$n,2),$r['sale_price'],$rows?1:0,'Şablondan üretildi ('.count($r['parts']).' parça)',$r['id']]);
  $pid=(int)$db->lastInsertId();
  $ins=$db->prepare("INSERT INTO print_materials(print_id,material_id,name,color,hex,unit,amount,cost,part) VALUES(?,?,?,?,?,?,?,?,?)");
  $dec=$db->prepare("UPDATE materials SET qty=GREATEST(0,qty-?) WHERE id=?");
  foreach($rows as $p){ $amt=(float)$p['amount']*$n;
    $ins->execute([$pid,$p['material_id'],$p['mname'],$p['mcolor'],$p['mhex'],$p['munit'],$amt,round(ek_pcost($p)*$n,2),$p['title']]);
    $dec->execute([$amt,$p['material_id']]); }
  return $pid;
}

/* ---------- Satış kaydı (sipariş takibi alanlarıyla) ---------- */
function ek_sale_vals($P){
  return ['sold_on'=>dt($P('sold_on')),'qty'=>max(1,(int)$P('qty')),'unit_price'=>max(0,num($P('unit_price'))),'fee'=>max(0,num($P('fee'))),'shipping'=>max(0,num($P('shipping'))),
    'channel'=>in_array($P('channel'),CHANNELS,true)?$P('channel'):'Diğer','customer'=>$P('customer'),'notes'=>$P('notes'),'status'=>sstat($P('status')),
    'due_on'=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$P('due_on'))?$P('due_on'):null,'paid'=>array_key_exists($P('paid'),PAID)?$P('paid'):'odendi','deposit'=>max(0,num($P('deposit'))),'phone'=>$P('phone')];
}
function ek_sale_save($db,$v,$id){
  $k=array_keys($v);
  if($id) $db->prepare("UPDATE sales SET ".implode('=?,',$k)."=? WHERE id=?")->execute(array_merge(array_values($v),[$id]));
  else $db->prepare("INSERT INTO sales(".implode(',',$k).") VALUES(".implode(',',array_fill(0,count($k),'?')).")")->execute(array_values($v));
}
function ek_wa($ph,$text){
  $d=preg_replace('/\D+/','',(string)$ph);
  if(strlen($d)===11 && $d[0]==='0') $d='9'.$d; elseif(strlen($d)===10 && $d[0]==='5') $d='90'.$d;
  return strlen($d)<11 ? '' : 'https://wa.me/'.$d.'?text='.rawurlencode($text);
}

/* ---------- Otomatik yedek: haftada bir e-postayla gönderilir ---------- */
function ek_dump($db){
  $bt=chr(96); $o="-- Mikro 3D Atölye veritabanı yedeği · ".date('d.m.Y H:i')."\n-- Geri yükleme: cPanel > phpMyAdmin > veritabanını seçin > İçe aktar.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
  foreach($db->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN,0) as $tb){
    $qn=$bt.str_replace($bt,$bt.$bt,$tb).$bt; $cr=$db->query("SHOW CREATE TABLE $qn")->fetch(PDO::FETCH_NUM)[1];
    $o.="DROP TABLE IF EXISTS $qn;\n$cr;\n";
    foreach($db->query("SELECT * FROM $qn",PDO::FETCH_ASSOC) as $r)
      $o.="INSERT INTO $qn (".$bt.implode($bt.','.$bt,array_keys($r)).$bt.") VALUES (".implode(',',array_map(fn($v)=>$v===null?'NULL':$db->quote((string)$v),$r)).");\n";
    $o.="\n";
  }
  return $o."SET FOREIGN_KEY_CHECKS=1;\n";
}
function ek_mail_backup($db,$to){
  if(!function_exists('mail')) return false;
  $sql=ek_dump($db); $gz=function_exists('gzencode'); $data=$gz?gzencode($sql,9):$sql; $fn='mikro3d-yedek-'.date('Y-m-d').($gz?'.sql.gz':'.sql');
  $b='m3d'.bin2hex(random_bytes(8)); $from=YEDEK_MAIL;
  $h="From: Atolye Paneli <$from>\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$b\"";
  $body="--$b\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\nAtölye panelinin otomatik yedeği ektedir (".date('d.m.Y H:i').").\r\nGeri yükleme: cPanel > phpMyAdmin > veritabanını seçin > İçe aktar.\r\n\r\n"
       ."--$b\r\nContent-Type: application/octet-stream; name=\"$fn\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$fn\"\r\n\r\n".chunk_split(base64_encode($data))."--$b--\r\n";
  return (bool)@mail($to,'=?UTF-8?B?'.base64_encode('Atölye paneli yedeği '.date('d.m.Y')).'?=',$body,$h);
}
function ek_backup_run($db,$to){
  try{ $ok=ek_mail_backup($db,$to); }catch(Throwable $e){ $ok=false; }
  $st=$db->prepare("REPLACE INTO settings(k,v) VALUES(?,?)");
  $st->execute(['auto_backup_try',(string)($ok?time():time()-6*86400)]);   // başarısızsa ertesi gün yeniden dener
  $st->execute(['auto_backup_res',($ok?'ok ':'hata ').date('d.m.Y H:i')]);
  return $ok;
}
function ek_auto_backup($db,$S){
  if(($S['auto_backup']??'1')!=='1') return;
  if(time()-(int)($S['auto_backup_try']??0) < 7*86400) return;
  ek_backup_run($db,$S['backup_mail']??YEDEK_MAIL);
}

/* ---------- Form işlemleri (admin.php'deki CSRF kontrolünden sonra çağrılır) ---------- */
function ek_post($db,$a,$id,$P,$watt,$kwh){
  // Ürün şablonları
  if($a==='radd'||$a==='redit'){
    if($P('name')==='') back('Ürün adı boş olamaz.');
    $v=[$P('name'),max(0,num($P('sale_price'))),max(0,num($P('extra_cost'))),$P('notes')];
    $db->beginTransaction();
    if($a==='radd'){ $db->prepare("INSERT INTO recipes(name,sale_price,extra_cost,notes) VALUES(?,?,?,?)")->execute($v); $rid=(int)$db->lastInsertId(); }
    else { $rid=$id; $v[]=$rid; $db->prepare("UPDATE recipes SET name=?,sale_price=?,extra_cost=?,notes=? WHERE id=?")->execute($v);
           $db->prepare("DELETE FROM recipe_parts WHERE recipe_id=?")->execute([$rid]); }
    $ins=$db->prepare("INSERT INTO recipe_parts(recipe_id,title,material_id,amount,hours) VALUES(?,?,?,?,?)");
    $tt=(array)($_POST['ptitle']??[]); $mm=(array)($_POST['pmat']??[]); $aa=(array)($_POST['pamt']??[]); $hh=(array)($_POST['phrs']??[]); $n=0;
    foreach($tt as $i=>$t0){ $t0=trim((string)$t0); $mid=(int)($mm[$i]??0); $am=max(0,num($aa[$i]??'')); $hr=max(0,num($hh[$i]??''));
      if($t0==='' && !$mid && $am<=0 && $hr<=0) continue;
      $n++; $ins->execute([$rid,$t0!==''?$t0:'Parça '.$n,$mid?:null,$am,$hr]); }
    $db->commit();
    back(($a==='radd'?'Şablon eklendi':'Şablon güncellendi').' ('.$n.' parça).');
  }
  if($a==='rdel'){ $db->prepare("DELETE FROM recipe_parts WHERE recipe_id=?")->execute([$id]); $db->prepare("DELETE FROM recipes WHERE id=?")->execute([$id]); back('Şablon silindi. Geçmiş baskı ve satışlar korunur.'); }
  if($a==='rmake'){
    $r=ek_one($db,$id); if(!$r) back();
    $n=max(1,(int)$P('n'));
    $db->beginTransaction(); ek_produce($db,$r,$n,dt($P('on')),$watt,$kwh); $db->commit();
    back($n.' adet "'.$r['name'].'" üretildi olarak kaydedildi. Malzeme stoktan düşüldü.');
  }
  if($a==='rsell'){
    $r=ek_one($db,$id); if(!$r) back();
    $c=ek_rcost($r,$watt,$kwh); $v=ek_sale_vals($P);
    $v['title']=$r['name']; $v['recipe_id']=$r['id']; $v['unit_cost']=round($c['total'],2); $v['print_id']=null;
    if($P('unit_price')==='') $v['unit_price']=(float)$r['sale_price'];
    $made=!empty($_POST['make']);
    $db->beginTransaction();
    if($made) $v['print_id']=ek_produce($db,$r,$v['qty'],$v['sold_on'],$watt,$kwh);
    ek_sale_save($db,$v,0);
    $db->commit();
    back('Satış kaydedildi.'.($made?' Ürün üretildi olarak işlendi, malzeme stoktan düşüldü.':''));
  }

  // Satışlar (sipariş takibi alanlarıyla; admin.php'deki eski sadd/sedit yerine çalışır)
  if($a==='sadd'){
    $pid=(int)$P('print_id'); $pr=null;
    if($pid){ $s=$db->prepare("SELECT * FROM prints WHERE id=?"); $s->execute([$pid]); $pr=$s->fetch()?:null; if(!$pr) $pid=0; }
    $title=$P('title'); if($title==='' && $pr) $title=$pr['title'];
    if($title==='') back('Ürün adı boş olamaz.');
    $v=ek_sale_vals($P); $v['title']=$title; $v['print_id']=$pid?:null;
    $v['unit_cost']=$P('unit_cost')==='' ? ($pr?round(pcost($pr),2):0) : max(0,num($P('unit_cost')));
    ek_sale_save($db,$v,0); back('Satış kaydedildi.');
  }
  if($a==='sedit'){
    if($P('title')==='') back('Ürün adı boş olamaz.');
    $v=ek_sale_vals($P); $v['title']=$P('title'); $v['unit_cost']=max(0,num($P('unit_cost')));
    ek_sale_save($db,$v,$id); back('Satış güncellendi.');
  }
  if($a==='skargo'||$a==='steslim'){ $ss=$a==='skargo'?'kargoda':'teslim'; $db->prepare("UPDATE sales SET status=? WHERE id=?")->execute([$ss,$id]); back('Sipariş durumu: '.SSTAT[$ss].'.'); }
  if($a==='spaid'){ $db->prepare("UPDATE sales SET paid='odendi' WHERE id=?")->execute([$id]); back('Ödeme alındı olarak işaretlendi.'); }

  // Giderler
  if($a==='gadd'){
    if($P('title')==='') back('Gider adı boş olamaz.');
    $db->prepare("INSERT INTO expenses(spent_on,title,category,amount,notes) VALUES(?,?,?,?,?)")->execute([dt($P('spent_on')),$P('title'),in_array($P('category'),GCATS,true)?$P('category'):'Diğer',max(0,num($P('amount'))),$P('notes')]);
    back('Gider kaydedildi.');
  }
  if($a==='gdel'){ $db->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]); back('Gider silindi.'); }

  // Malzeme alışı: stoğu artırır, birim maliyeti son alış fiyatına göre günceller
  if($a==='buy'){
    $mid=(int)$P('material_id'); $qty=max(0,num($P('qty'))); $price=max(0,num($P('price')));
    $s=$db->prepare("SELECT * FROM materials WHERE id=?"); $s->execute([$mid]); $m=$s->fetch();
    if(!$m || $qty<=0) back('Malzeme seçin ve alınan miktarı girin.');
    $db->beginTransaction();
    $db->prepare("INSERT INTO purchases(bought_on,material_id,name,qty,price,notes) VALUES(?,?,?,?,?,?)")->execute([dt($P('bought_on')),$mid,$m['name'].($m['color']?' · '.$m['color']:''),$qty,$price,$P('notes')]);
    $db->prepare("UPDATE materials SET qty=qty+? WHERE id=?")->execute([$qty,$mid]);
    if($price>0 && (float)$m['pack_size']>0) $db->prepare("UPDATE materials SET pack_price=? WHERE id=?")->execute([round($price/$qty*(float)$m['pack_size'],2),$mid]);
    $db->commit();
    back('Alış kaydedildi. Stok'.($price>0?' ve birim maliyet':'').' güncellendi.');
  }
  if($a==='buydel'){ $db->prepare("DELETE FROM purchases WHERE id=?")->execute([$id]); back('Alış kaydı silindi. Stok miktarı değiştirilmedi.'); }

  // Baskıyı bugünün tarihiyle kopyalar (güncel fiyatlarla), malzemeyi stoktan düşer
  if($a==='bcopy'){
    $s=$db->prepare("SELECT * FROM prints WHERE id=?"); $s->execute([$id]); $o=$s->fetch(); if(!$o) back();
    $s=$db->prepare("SELECT pm.*, m.pack_price, m.pack_size FROM print_materials pm LEFT JOIN materials m ON m.id=pm.material_id WHERE pm.print_id=? ORDER BY pm.id"); $s->execute([$id]); $rows=$s->fetchAll();
    $mc=0; $costs=[];
    foreach($rows as $i=>$x){ $costs[$i]=($x['pack_size']!==null && (float)$x['pack_size']>0) ? round((float)$x['amount']*(float)$x['pack_price']/(float)$x['pack_size'],2) : (float)$x['cost']; $mc+=$costs[$i]; }
    $db->beginTransaction();
    $db->prepare("INSERT INTO prints(printed_on,title,qty,hours,material_cost,power_cost,extra_cost,sale_price,status,deducted,notes,recipe_id) VALUES(?,?,?,?,?,?,?,?,'ok',?,?,?)")
       ->execute([date('Y-m-d'),$o['title'],$o['qty'],$o['hours'],$mc,round((float)$o['hours']*$watt/1000*$kwh,2),$o['extra_cost'],$o['sale_price'],$rows?1:0,$o['notes'],$o['recipe_id']]);
    $pid=(int)$db->lastInsertId();
    $ins=$db->prepare("INSERT INTO print_materials(print_id,material_id,name,color,hex,unit,amount,cost,part) VALUES(?,?,?,?,?,?,?,?,?)");
    $dec=$db->prepare("UPDATE materials SET qty=GREATEST(0,qty-?) WHERE id=?");
    foreach($rows as $i=>$x){ $ins->execute([$pid,$x['material_id'],$x['name'],$x['color'],$x['hex'],$x['unit'],$x['amount'],$costs[$i],$x['part']]); if($x['material_id']) $dec->execute([$x['amount'],$x['material_id']]); }
    $db->commit();
    back('Baskı bugünün tarihiyle kopyalandı.'.($rows?' Malzeme stoktan düşüldü.':''));
  }
  // Bir baskının kullandığı malzemeleri düzenler; stok farkını işler
  if($a==='bmat'){
    $s=$db->prepare("SELECT * FROM prints WHERE id=?"); $s->execute([$id]); $o=$s->fetch(); if(!$o) back();
    $ded=(int)$o['deducted']; $ids=(array)($_POST['pmid']??[]); $am=(array)($_POST['pmamt']??[]);
    $get=$db->prepare("SELECT pm.*, m.pack_price, m.pack_size FROM print_materials pm LEFT JOIN materials m ON m.id=pm.material_id WHERE pm.id=? AND pm.print_id=?");
    $adj=$db->prepare("UPDATE materials SET qty=GREATEST(0,qty+?) WHERE id=?");
    $db->beginTransaction();
    foreach($ids as $i=>$pmid){
      $get->execute([(int)$pmid,$id]); $x=$get->fetch(); if(!$x) continue;
      $new=max(0,num($am[$i]??'')); $old=(float)$x['amount'];
      if($ded && $x['material_id']) $adj->execute([$old-$new,$x['material_id']]);
      if($new<=0){ $db->prepare("DELETE FROM print_materials WHERE id=?")->execute([$x['id']]); continue; }
      $unit=$old>0 ? (float)$x['cost']/$old : (($x['pack_size']!==null && (float)$x['pack_size']>0) ? (float)$x['pack_price']/(float)$x['pack_size'] : 0);
      $db->prepare("UPDATE print_materials SET amount=?,cost=? WHERE id=?")->execute([$new,round($unit*$new,2),$x['id']]);
    }
    $nm=(int)$P('newmat'); $na=max(0,num($P('newamt')));
    if($nm && $na>0){
      $s=$db->prepare("SELECT * FROM materials WHERE id=?"); $s->execute([$nm]); $m=$s->fetch();
      if($m){
        $db->prepare("INSERT INTO print_materials(print_id,material_id,name,color,hex,unit,amount,cost) VALUES(?,?,?,?,?,?,?,?)")->execute([$id,$nm,$m['name'],$m['color'],$m['hex'],$m['unit'],$na,round(ucost($m)*$na,2)]);
        if($ded || !$ids){ $adj->execute([-$na,$nm]); $ded=1; }
      }
    }
    $s=$db->prepare("SELECT COALESCE(SUM(cost),0) FROM print_materials WHERE print_id=?"); $s->execute([$id]);
    $db->prepare("UPDATE prints SET material_cost=?, deducted=? WHERE id=?")->execute([$s->fetchColumn(),$ded,$id]);
    $db->commit();
    back('Baskının malzemeleri güncellendi.'.($ded?' Stok farkı işlendi.':''));
  }

  // Otomatik yedek ayarları
  if($a==='setb'){
    $ml=filter_var($P('backup_mail'),FILTER_VALIDATE_EMAIL) ? $P('backup_mail') : YEDEK_MAIL;
    $st=$db->prepare("REPLACE INTO settings(k,v) VALUES(?,?)");
    $st->execute(['backup_mail',$ml]); $st->execute(['auto_backup',empty($_POST['auto_backup'])?'0':'1']);
    if($P('simdi')==='1') back(ek_backup_run($db,$ml) ? 'Yedek '.$ml.' adresine gönderildi. Gelen kutunuzu kontrol edin.' : 'Yedek gönderilemedi: sunucu e-postayı kabul etmedi. Elle "Yedek indir" kullanmaya devam edin.');
    back('Yedek ayarları kaydedildi.');
  }
}

/* ---------- Ekran parçaları ---------- */
function ek_nav($t){
  $o=''; foreach(['o'=>'Özet','m'=>'Stok','r'=>'Şablonlar','b'=>'Baskılar','s'=>'Satışlar','g'=>'Giderler','p'=>'Site ürünleri'] as $k=>$v) $o.='<a href="?t='.$k.'"'.($t===$k?' class="on"':'').'>'.$v.'</a>';
  return $o;
}
function ek_page($t,$db,$C,$S,$watt,$kwh,$mats,$today){
  if($t==='o'){ ek_ozet($db,$C,$S,$mats); return true; }
  if($t==='r'){ ek_sablon($db,$C,$watt,$kwh,$mats,$today); return true; }
  if($t==='g'){ ek_gider($db,$C,$today); return true; }
  return false;
}
// Satış formlarına eklenen sipariş takibi alanları
function ek_sale_fields($s){
  $pd=$s['paid']??'odendi';
  $o='<label class="lbl">Telefon<input name="phone" inputmode="tel" value="'.h((string)($s['phone']??'')).'" placeholder="05xx xxx xx xx"></label>'
    .'<label class="lbl">Teslim tarihi<input type="date" name="due_on" value="'.h((string)($s['due_on']??'')).'"></label>'
    .'<label class="lbl">Ödeme<select name="paid">';
  foreach(PAID as $k=>$v) $o.='<option value="'.$k.'"'.($pd===$k?' selected':'').'>'.$v.'</option>';
  $dep=(isset($s['deposit']) && $s['deposit']>0) ? (string)($s['deposit']+0) : '';
  return $o.'</select></label><label class="lbl">Alınan kapora (₺)<input name="deposit" inputmode="decimal" value="'.h($dep).'" placeholder="0"></label>';
}
function ek_sale_info($s){
  $o=''; $pd=$s['paid']??'odendi';
  if($pd!=='odendi') $o.='<span class="badge">'.h(PAID[$pd]??$pd).(($pd==='kapora' && $s['deposit']>0)?' '.tl($s['deposit']):'').'</span>';
  if(!empty($s['due_on']) && ($s['status']??'')!=='teslim') $o.='<span class="badge t">Teslim: '.day($s['due_on']).'</span>';
  return $o;
}
// Teslim edilmemiş siparişler: hızlı durum, ödeme ve WhatsApp düğmeleriyle
function ek_pending($db,$C){
  $pend=$db->query("SELECT * FROM sales WHERE status<>'teslim' ORDER BY COALESCE(due_on,sold_on), id")->fetchAll(); if(!$pend) return;
  $now=date('Y-m-d'); ?>
<div class="box warnb"><b>Bekleyen siparişler (<?=count($pend)?>)</b>
<?php foreach($pend as $s): $late=!empty($s['due_on']) && $s['due_on']<$now; $wa=ek_wa($s['phone']??'','Merhaba, "'.$s['title'].'" siparişiniz hazır.'); ?>
<div class="prow2"><span><?=day($s['sold_on'])?> · <b><?=h($s['title'])?></b><?=$s['customer']?' · '.h($s['customer']):''?> <span class="badge<?=$s['status']==='kargoda'?' t':''?>"><?=h(SSTAT[$s['status']]??$s['status'])?></span><?=ek_sale_info($s)?><?=$late?' <b class="neg">gecikti</b>':''?></span>
<form method="post" class="inl"><?=$C?><input type="hidden" name="id" value="<?=$s['id']?>">
<?php if($wa): ?><a class="btn alt" href="<?=h($wa)?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
<?php if(($s['paid']??'odendi')!=='odendi'): ?><button class="alt" name="a" value="spaid">Ödendi</button><?php endif; ?>
<?php if($s['status']==='hazirlaniyor'): ?><button class="alt" name="a" value="skargo">Kargoya verildi</button><?php endif; ?>
<button name="a" value="steslim">Teslim edildi</button></form></div>
<?php endforeach; ?></div>
<?php }

/* ---------- Özet ---------- */
function ek_ozet($db,$C,$S,$mats){
  $f=date('Y-m-01'); $l=date('Y-m-t');
  $s=$db->prepare("SELECT COUNT(*) n, COALESCE(SUM(qty*unit_price),0) ciro, COALESCE(SUM(qty*unit_price-fee-shipping-qty*unit_cost),0) kar FROM sales WHERE sold_on BETWEEN ? AND ?"); $s->execute([$f,$l]); $m=$s->fetch();
  $s=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE spent_on BETWEEN ? AND ?"); $s->execute([$f,$l]); $gid=(float)$s->fetchColumn();
  $start=date('Y-m-01',strtotime('-5 months',strtotime($f)));
  $sk=[]; $s=$db->prepare("SELECT DATE_FORMAT(sold_on,'%Y-%m') ym, SUM(qty*unit_price-fee-shipping-qty*unit_cost) kar FROM sales WHERE sold_on>=? GROUP BY ym"); $s->execute([$start]); foreach($s->fetchAll() as $x) $sk[$x['ym']]=(float)$x['kar'];
  $gk=[]; $s=$db->prepare("SELECT DATE_FORMAT(spent_on,'%Y-%m') ym, SUM(amount) g FROM expenses WHERE spent_on>=? GROUP BY ym"); $s->execute([$start]); foreach($s->fetchAll() as $x) $gk[$x['ym']]=(float)$x['g'];
  $mon=[]; $max=1;
  for($i=5;$i>=0;$i--){ $ym=date('Y-m',strtotime('-'.$i.' months',strtotime($f))); $mon[$ym]=($sk[$ym]??0)-($gk[$ym]??0); $max=max($max,abs($mon[$ym])); }
  $pend=(int)$db->query("SELECT COUNT(*) FROM sales WHERE status<>'teslim'")->fetchColumn();
  $acik=(float)$db->query("SELECT COALESCE(SUM(qty*unit_price-deposit),0) FROM sales WHERE paid<>'odendi'")->fetchColumn();
  $low=array_filter($mats,fn($x)=>$x['qty']<=$x['min_qty']);
  $top=$db->query("SELECT title, SUM(qty) n, SUM(qty*unit_price) c FROM sales GROUP BY title ORDER BY n DESC, c DESC LIMIT 6")->fetchAll();
  $cus=$db->query("SELECT customer, MAX(phone) phone, COUNT(*) n, SUM(qty*unit_price) c, MAX(sold_on) son FROM sales WHERE customer IS NOT NULL AND customer<>'' GROUP BY customer ORDER BY c DESC LIMIT 12")->fetchAll();
  $net=(float)$m['kar']-$gid; $res=(string)($S['auto_backup_res']??''); $auto=($S['auto_backup']??'1')==='1'; ?>
<div class="cards">
<div><small><?=AYLAR[(int)date('n')]?> · ciro</small><b><?=tl($m['ciro'])?></b></div>
<div><small>Satış kârı</small><b class="<?=$m['kar']<0?'neg':'pos'?>"><?=tl($m['kar'])?></b></div>
<div><small>Giderler</small><b><?=tl($gid)?></b></div>
<div><small>Net kâr</small><b class="<?=$net<0?'neg':'pos'?>"><?=tl($net)?></b></div>
<div><small>Bekleyen sipariş</small><b><?=$pend?></b></div>
<div><small>Tahsil edilecek</small><b class="<?=$acik>0?'neg':''?>"><?=tl($acik)?></b></div>
</div>
<h2 class="st">Son 6 ay · net kâr (satış kârı − giderler)</h2>
<div class="bars"><?php foreach($mon as $ym=>$v): ?><div><span class="bw"><i class="<?=$v<0?'neg':''?>" style="height:<?=max(2,(int)round(abs($v)/$max*100))?>%"></i></span><b class="<?=$v<0?'neg':''?>"><?=tl($v)?></b><small><?=AYLAR[(int)substr($ym,5,2)]?></small></div><?php endforeach; ?></div>
<?php ek_pending($db,$C); ?>
<div class="cols2">
<div class="box"><b>En çok satanlar</b>
<?php foreach($top as $x): ?><p><span><?=h($x['title'])?></span><span><?=(int)$x['n']?> adet · <?=tl($x['c'])?></span></p><?php endforeach; ?>
<?php if(!$top): ?><p><span class="hint">Henüz satış yok.</span></p><?php endif; ?></div>
<div class="box"><b>Azalan malzemeler</b>
<?php foreach($low as $x): ?><p><span><?=sw($x['hex'])?><?=h($x['name'])?></span><span class="neg"><?=q($x['qty'])?> <?=h($x['unit'])?></span></p><?php endforeach; ?>
<?php if(!$low): ?><p><span class="hint">Azalan malzeme yok.</span></p><?php endif; ?>
<p><a href="?t=m">Stok sekmesine git</a></p></div>
</div>
<div class="box"><b>Müşteriler</b>
<?php foreach($cus as $x): $wa=ek_wa($x['phone']??'','Merhaba'); ?><p><span><b><?=h($x['customer'])?></b> · <?=(int)$x['n']?> sipariş · son: <?=day($x['son'])?></span><span><?=tl($x['c'])?><?php if($wa): ?> · <a href="<?=h($wa)?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></span></p><?php endforeach; ?>
<?php if(!$cus): ?><p><span class="hint">Satış girerken müşteri adı yazdıkça burada listelenir.</span></p><?php endif; ?></div>
<details class="add"><summary>Otomatik yedek · <?=$auto?'açık':'kapalı'?><?=$res!==''?' · son gönderim: '.h($res):''?></summary>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="setb">
<label class="lbl full2">Yedeğin gönderileceği e-posta<input type="email" name="backup_mail" value="<?=h((string)($S['backup_mail']??YEDEK_MAIL))?>"></label>
<label class="ck"><input type="checkbox" name="auto_backup" value="1"<?=$auto?' checked':''?>> Haftada bir otomatik gönder</label>
<button>Kaydet</button><button class="alt" name="simdi" value="1">Kaydet ve şimdi gönder</button>
<p class="full hint">Paneli açtığınızda, son gönderimin üzerinden 7 gün geçtiyse tüm kayıtların yedeği bu adrese e-postayla gönderilir. E-posta kutusu da aynı sunucuda durduğu için ayda bir "Yedek indir" ile kendi cihazınıza da yedek almaya devam edin.</p>
</form></details>
<?php }

/* ---------- Giderler ---------- */
function ek_gider($db,$C,$today){
  [$w,$pa]=per('spent_on');
  $s=$db->prepare("SELECT * FROM expenses WHERE $w ORDER BY spent_on DESC, id DESC LIMIT 300"); $s->execute($pa); $rows=$s->fetchAll();
  $tot=0; $by=[]; foreach($rows as $x){ $tot+=$x['amount']; $by[$x['category']]=($by[$x['category']]??0)+$x['amount']; } arsort($by); ?>
<div class="cards"><div><small><?=h(plabel())?> · gider</small><b><?=tl($tot)?></b></div><div><small>Kayıt</small><b><?=count($rows)?></b></div></div>
<?php if($by): ?><p class="chs"><?php foreach($by as $k=>$v): ?><span><b><?=h($k)?></b> <?=tl($v)?></span><?php endforeach; ?></p><?php endif; ?>
<details class="add" open><summary>+ Gider ekle</summary>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="gadd">
<label class="lbl">Tarih<input type="date" name="spent_on" value="<?=$today?>"></label>
<label class="lbl full2">Ne için?<input name="title" required placeholder="örn. Kargo poşeti, nozzle, Shopier ücreti"></label>
<label class="lbl">Tutar (₺)<input name="amount" inputmode="decimal" required></label>
<label class="lbl">Tür<select name="category"><?php foreach(GCATS as $g): ?><option><?=$g?></option><?php endforeach; ?></select></label>
<label class="lbl full">Not<textarea name="notes" rows="2"></textarea></label>
<button>Gideri kaydet</button></form></details>
<p class="hint">Filament alışlarını buraya değil, Stok sekmesindeki "Alış kaydet" bölümüne girin. Filament maliyeti baskıların maliyetine zaten dahil olduğu için burada ikinci kez sayılmamalı.</p>
<div class="x"><table><tr><th>Tarih</th><th>Gider</th><th>Tür</th><th>Tutar</th><th></th></tr>
<?php foreach($rows as $x): ?><tr><td class="nowrap"><?=day($x['spent_on'])?></td><td><b><?=h($x['title'])?></b><?=$x['notes']?'<br><small>'.h($x['notes']).'</small>':''?></td><td><?=h($x['category'])?></td><td class="nowrap"><?=tl($x['amount'])?></td>
<td class="act"><form method="post"><?=$C?><input type="hidden" name="id" value="<?=$x['id']?>"><button class="lnk neg" name="a" value="gdel" onclick="return confirm('Bu gider silinsin mi?')">Sil</button></form></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="5" class="empty"><?=h(plabel())?> için kayıtlı gider yok.</td></tr><?php endif; ?></table></div>
<?php }

/* ---------- Stok sekmesinin altı: alışveriş listesi ve alış kayıtları ---------- */
function ek_stok($db,$C,$mats,$matOpts,$today){
  if(!$mats) return;
  $low=array_filter($mats,fn($x)=>$x['qty']<=$x['min_qty']);
  $buys=$db->query("SELECT * FROM purchases ORDER BY bought_on DESC, id DESC LIMIT 15")->fetchAll(); ?>
<?php if($low): ?><div class="box warnb"><b>Alışveriş listesi</b>
<?php foreach($low as $x): ?><p><span><?=sw($x['hex'])?><?=h($x['name'])?><?=$x['color']?' · '.h($x['color']):''?></span><span>kalan <?=q($x['qty'])?> <?=h($x['unit'])?><?=$x['pack_size']>0?' · 1 paket '.q($x['pack_size']).' '.h($x['unit']).($x['pack_price']>0?' ≈ '.tl($x['pack_price']):''):''?></span></p><?php endforeach; ?></div><?php endif; ?>
<details class="add"><summary>+ Alış kaydet (stok ve birim maliyet güncellenir)</summary>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="buy">
<label class="lbl">Tarih<input type="date" name="bought_on" value="<?=$today?>"></label>
<label class="lbl full2">Malzeme<select name="material_id" required><?=$matOpts?></select></label>
<label class="lbl">Alınan miktar<input name="qty" inputmode="decimal" required placeholder="örn. 1000"></label>
<label class="lbl">Ödenen toplam (₺)<input name="price" inputmode="decimal" placeholder="örn. 650"></label>
<label class="lbl full">Not<input name="notes" placeholder="satıcı, kampanya..."></label>
<button>Alışı kaydet</button></form></details>
<?php if($buys): ?><h2 class="st">Son alışlar</h2>
<div class="x"><table><tr><th>Tarih</th><th>Malzeme</th><th>Miktar</th><th>Tutar</th><th></th></tr>
<?php foreach($buys as $x): ?><tr><td class="nowrap"><?=day($x['bought_on'])?></td><td><b><?=h($x['name'])?></b><?=$x['notes']?'<br><small>'.h($x['notes']).'</small>':''?></td><td class="nowrap"><?=q($x['qty'])?></td><td class="nowrap"><?=$x['price']>0?tl($x['price']):'—'?></td>
<td class="act"><form method="post"><?=$C?><input type="hidden" name="id" value="<?=$x['id']?>"><button class="lnk neg" name="a" value="buydel" onclick="return confirm('Alış kaydı silinsin mi? Stok miktarı değişmez.')">Sil</button></form></td></tr><?php endforeach; ?>
</table></div><?php endif; ?>
<?php }

/* ---------- Baskı satırında malzeme düzenleme formu ---------- */
function ek_bmat_form($p,$rows,$C,$matOpts){ ?>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="bmat"><input type="hidden" name="id" value="<?=$p['id']?>">
<div class="full lbl">Kullanılan malzemeler (miktarı 0 yaparsanız o satır silinir)</div>
<?php foreach($rows as $x): ?><label class="lbl"><?=!empty($x['part'])?h($x['part']).': ':''?><?=h($x['name'])?> (<?=h($x['unit'])?>)<input type="hidden" name="pmid[]" value="<?=$x['id']?>"><input name="pmamt[]" inputmode="decimal" value="<?=h((string)($x['amount']+0))?>"></label><?php endforeach; ?>
<label class="lbl full2">Malzeme ekle<select name="newmat"><?=$matOpts?></select></label>
<label class="lbl">Miktar<input name="newamt" inputmode="decimal" placeholder="0"></label>
<button class="alt">Malzemeleri kaydet</button></form>
<?php }

/* ---------- Ürün şablonları ekranı ---------- */
function ek_mopts($mats,$sel=0){
  $o='<option value="">Malzeme seçin</option>';
  foreach($mats as $m) $o.='<option value="'.$m['id'].'" data-uc="'.ucost($m).'"'.((int)$sel===(int)$m['id']?' selected':'').'>'.h($m['name']).(($m['color']!==''&&$m['color']!==null)?' · '.h($m['color']):'').' ('.q($m['qty']).' '.h($m['unit']).')</option>';
  return $o;
}
// Bir parça satırı: ad · malzeme · miktar · baskı süresi
function ek_prow($mats,$p=null){
  return '<div class="prow"><input name="ptitle[]" placeholder="Parça adı (örn. Gövde)" value="'.h((string)($p['title']??'')).'" aria-label="Parça adı">'
    .'<select name="pmat[]" aria-label="Malzeme">'.ek_mopts($mats,$p['material_id']??0).'</select>'
    .'<input name="pamt[]" inputmode="decimal" placeholder="gram" value="'.h($p?(string)($p['amount']+0):'').'" aria-label="Miktar">'
    .'<input name="phrs[]" inputmode="decimal" placeholder="saat" value="'.h($p?(string)($p['hours']+0):'').'" aria-label="Baskı süresi (saat)"></div>';
}
function ek_rform($C,$mats,$r=null){
  $o='<form method="post" class="row rform">'.$C.'<input type="hidden" name="a" value="'.($r?'redit':'radd').'">'.($r?'<input type="hidden" name="id" value="'.$r['id'].'">':'')
    .'<label class="lbl full2">Ürün adı<input name="name" required value="'.h((string)($r['name']??'')).'" placeholder="örn. Ledli İsimlik"></label>'
    .'<label class="lbl">Satış fiyatı (₺)<input name="sale_price" inputmode="decimal" value="'.h($r?(string)($r['sale_price']+0):'').'"></label>'
    .'<label class="lbl">Diğer maliyet / adet (₺)<input name="extra_cost" inputmode="decimal" value="'.h($r?(string)($r['extra_cost']+0):'').'" placeholder="led, kablo, ambalaj"></label>'
    .'<div class="full"><div class="lbl" style="margin-bottom:8px">Parçalar: her satır ayrı bir baskı (ad · malzeme · gram · saat)</div>';
  foreach(($r['parts']??[]) as $p) $o.=ek_prow($mats,$p);
  for($i=0;$i<($r?2:4);$i++) $o.=ek_prow($mats);
  return $o.'<p class="hint">Boş satırlar kaydedilmez. Daha fazla parça gerekirse kaydedip Düzenle ile devam edin; her açılışta 2 boş satır eklenir.</p></div>'
    .'<label class="lbl full">Not<textarea name="notes" rows="2">'.h((string)($r['notes']??'')).'</textarea></label>'
    .'<div class="calc"></div><button>'.($r?'Şablonu kaydet':'Şablonu ekle').'</button></form>';
}
function ek_sablon($db,$C,$watt,$kwh,$mats,$today){
  $rs=ek_recipes($db); ?>
<p class="hint">Bir ürünü bir kez tanımlayın: hangi parçalardan oluşuyor, her parça hangi malzemeden kaç gram ve kaç saatte basılıyor. Sonra tek dokunuşla "Ürettim" ya da "Sattım" diyebilirsiniz.</p>
<?php if(!$mats): ?><p class="msg warn">Parçalara malzeme seçebilmek için önce Stok sekmesinden malzeme ekleyin.</p><?php endif; ?>
<details class="add"<?=$rs?'':' open'?>><summary>+ Yeni ürün şablonu</summary><?=ek_rform($C,$mats)?></details>
<details class="add"><summary>Fiyat hesaplayıcı</summary>
<form class="row" id="fcalc" onsubmit="return false">
<label class="lbl">Maliyet (₺)<input name="c" inputmode="decimal"></label>
<label class="lbl">Hedef kâr (%)<input name="m" inputmode="decimal" value="100"></label>
<label class="lbl">Platform komisyonu (%)<input name="k" inputmode="decimal" value="0"></label>
<label class="lbl">Kargo – sizin ödediğiniz (₺)<input name="s" inputmode="decimal" value="0"></label>
<div class="calc" id="fout">Maliyeti girin.</div></form></details>
<div class="rcs" data-watt="<?=$watt?>" data-kwh="<?=$kwh?>">
<?php foreach($rs as $r):
  $c=ek_rcost($r,$watt,$kwh); $kar=(float)$r['sale_price']-$c['total'];
  $need=[]; $have=[];
  foreach($r['parts'] as $p){ if($p['mname']===null || (float)$p['amount']<=0) continue; $mid=(int)$p['material_id']; $need[$mid]=($need[$mid]??0)+(float)$p['amount']; $have[$mid]=(float)$p['mqty']; }
  $can=null; foreach($need as $mid=>$nd){ $kk=(int)floor($have[$mid]/$nd); $can=($can===null)?$kk:min($can,$kk); } ?>
<article class="rc">
<header><h2><?=h($r['name'])?></h2><span><?=count($r['parts'])?> parça · toplam <?=q($c['hours'])?> saat baskı</span></header>
<ul class="parts"><?php foreach($r['parts'] as $p): ?><li><b><?=h($p['title'])?></b><span><?php if($p['mname']!==null): ?><?=sw($p['mhex'])?><?=h($p['mname'])?> · <?=q($p['amount'])?> <?=h($p['munit'])?><?php else: ?>malzeme seçilmedi<?php endif; ?><?=$p['hours']>0?' · '.q($p['hours']).' sa':''?></span><em><?=tl(ek_pcost($p))?></em></li><?php endforeach; ?>
<?php if(!$r['parts']): ?><li><span class="hint">Henüz parça eklenmedi. Düzenle ile ekleyin.</span></li><?php endif; ?></ul>
<div class="cards"><div><small>Maliyet / adet</small><b><?=tl($c['total'])?></b></div><div><small>Satış fiyatı</small><b><?=tl($r['sale_price'])?></b></div><div><small>Kâr / adet</small><b class="<?=$kar<0?'neg':'pos'?>"><?=tl($kar)?></b></div></div>
<p class="hint">Malzeme <?=tl($c['mat'])?> · elektrik <?=tl($c['pow'])?> · diğer <?=tl($c['extra'])?><?=$can!==null?' · <b class="'.($can<1?'neg':'').'">stok '.$can.' adet üretmeye yeter</b>':''?><?=$r['notes']?' · '.h($r['notes']):''?></p>
<form method="post" class="inl"><?=$C?><input type="hidden" name="a" value="rmake"><input type="hidden" name="id" value="<?=$r['id']?>"><input name="n" type="number" min="1" value="1" aria-label="Adet" style="max-width:84px"><input type="date" name="on" value="<?=$today?>" aria-label="Tarih"><button>Ürettim</button></form>
<details class="ed"><summary>Sattım / sipariş aldım</summary>
<form method="post" class="row"><?=$C?><input type="hidden" name="a" value="rsell"><input type="hidden" name="id" value="<?=$r['id']?>">
<label class="lbl">Tarih<input type="date" name="sold_on" value="<?=$today?>"></label>
<label class="lbl">Adet<input name="qty" type="number" min="1" value="1"></label>
<label class="lbl">Birim fiyat (₺)<input name="unit_price" inputmode="decimal" value="<?=h((string)($r['sale_price']+0))?>"></label>
<label class="lbl">Kanal<select name="channel"><?php foreach(CHANNELS as $ch): ?><option><?=$ch?></option><?php endforeach; ?></select></label>
<label class="lbl">Komisyon (₺)<input name="fee" inputmode="decimal" placeholder="0"></label>
<label class="lbl">Kargo – sizin ödediğiniz (₺)<input name="shipping" inputmode="decimal" placeholder="0"></label>
<label class="lbl">Müşteri<input name="customer" placeholder="ad ya da kullanıcı adı"></label>
<label class="lbl">Sipariş durumu<select name="status"><?php foreach(SSTAT as $sk=>$sv): ?><option value="<?=$sk?>"><?=$sv?></option><?php endforeach; ?></select></label>
<?=ek_sale_fields(null)?>
<label class="lbl full">Not<textarea name="notes" rows="2" placeholder="isim, renk, özel istek..."></textarea></label>
<label class="ck full"><input type="checkbox" name="make" value="1"> Ürün şimdi üretildi: baskı olarak da kaydet ve malzemeyi stoktan düş</label>
<button>Satışı kaydet</button></form></details>
<details class="ed"><summary>Düzenle</summary><?=ek_rform($C,$mats,$r)?></details>
<form method="post"><?=$C?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="lnk neg" name="a" value="rdel" onclick="return confirm('Bu şablon silinsin mi? Geçmiş baskı ve satışlar korunur.')">Sil</button></form>
</article>
<?php endforeach; if(!$rs): ?><p class="empty">Henüz ürün şablonu yok. Yukarıdan ilk ürününüzü tanımlayın.</p><?php endif; ?>
</div>
<script>
(function(){
  function n(v){ v=String(v||'').trim(); if(v.indexOf(',')>-1) v=v.replace(/[.\s]/g,'').replace(',','.'); return parseFloat(v)||0; }
  function tl(x){ return x.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺'; }
  var box=document.querySelector('.rcs'), W=+box.dataset.watt||0, K=+box.dataset.kwh||0;
  // Şablon formu: parçaları yazarken maliyeti anında hesaplar
  document.querySelectorAll('form.rform').forEach(function(f){
    var out=f.querySelector('.calc');
    function calc(){
      var mc=0, h=0, np=0;
      f.querySelectorAll('.prow').forEach(function(r){ var o=r.querySelector('select').selectedOptions[0], i=r.querySelectorAll('input'), a=n(i[1].value), hr=n(i[2].value);
        if(o&&o.value) mc+=(+o.dataset.uc||0)*a; h+=hr; if(i[0].value.trim()||a||hr||(o&&o.value)) np++; });
      var pc=h*W/1000*K, ex=n(f.elements.namedItem('extra_cost').value), tot=mc+pc+ex, sp=n(f.elements.namedItem('sale_price').value);
      out.innerHTML=np+' parça · '+h.toLocaleString('tr-TR')+' saat · Malzeme <b>'+tl(mc)+'</b> · Elektrik <b>'+tl(pc)+'</b>'+(W&&K?'':' <small>(elektrik ayarı yok)</small>')+' · Diğer <b>'+tl(ex)+'</b><br>Maliyet / adet <b>'+tl(tot)+'</b>'+(sp>0?' · Kâr / adet <b class="'+(sp-tot<0?'neg':'pos')+'">'+tl(sp-tot)+'</b>':'');
    }
    f.addEventListener('input',calc); f.addEventListener('change',calc); calc();
  });
  // Fiyat hesaplayıcı
  var fc=document.getElementById('fcalc'), fo=document.getElementById('fout');
  fc.addEventListener('input',function(){
    var E=function(k){ return n(fc.elements.namedItem(k).value); }, c=E('c'), s=E('s'), k=Math.min(95,E('k'))/100, p=(c+s)*(1+E('m')/100)/(1-k);
    fo.innerHTML=c>0 ? 'Önerilen satış fiyatı <b>'+tl(p)+'</b> · komisyon '+tl(p*k)+' · elinize kalan kâr <b class="pos">'+tl(p-p*k-s-c)+'</b>' : 'Maliyeti girin.';
  });
})();
</script>
<?php }
