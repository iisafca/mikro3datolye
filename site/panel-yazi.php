<?php
/* Atölye paneli: Yazılar sekmesi. Sitedeki rehber yazıları (/rehber/...) buradan eklenir, düzenlenir ve yayınlanır.
   admin.php tarafından yüklenir; tek başına bir sayfa değildir. admin.php'deki saveImg, urlImg, dt, day ve back yardımcılarını kullanır. */
if(!function_exists('h')){ http_response_code(404); exit; }   // doğrudan açılırsa hiçbir şey göstermez
require_once __DIR__.'/yazi-ortak.php';

/* Tablo yoksa oluşturur (bir kez çalışır). */
function yazi_migrate($db,$S){
  if(($S['yazi_v']??'')==='1') return;
  $db->exec("CREATE TABLE IF NOT EXISTS posts(
    id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(80) NOT NULL, title VARCHAR(160) NOT NULL, description VARCHAR(300) NOT NULL DEFAULT '',
    body MEDIUMTEXT, image VARCHAR(80) NULL, published TINYINT(1) NOT NULL DEFAULT 0, published_on DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY(slug), INDEX(published,published_on)) CHARSET=utf8mb4");
  $db->exec("REPLACE INTO settings(k,v) VALUES('yazi_v','1')");
}

/* Adres: girilmezse başlıktan üretilir; başka bir yazıda kullanılıyorsa sonuna -2, -3 eklenir. */
function yazi_slug($db,$istek,$baslik,$id){
  $s=slug($istek!=='' ? $istek : $baslik); if($s==='urun') $s='yazi';
  $s=substr($s,0,70); $base=$s; $i=2;
  $q=$db->prepare("SELECT COUNT(*) FROM posts WHERE slug=? AND id<>?");
  while(true){ $q->execute([$s,$id]); if(!(int)$q->fetchColumn()) return $s; $s=$base.'-'.$i++; }
}

function yazi_post($db,$a,$id,$P){
  if(!in_array($a,['yadd','yedit','ytog','ydel'],true)) return;
  if($a==='ytog'){
    $db->prepare("UPDATE posts SET published=1-published, published_on=COALESCE(published_on,CURDATE()) WHERE id=?")->execute([$id]);
    back('Yazının durumu değişti.');
  }
  if($a==='ydel'){
    $r=$db->prepare("SELECT image FROM posts WHERE id=?"); $r->execute([$id]); $im=$r->fetchColumn();
    if($im) @unlink(__DIR__.'/uploads/'.basename($im));
    $db->prepare("DELETE FROM posts WHERE id=?")->execute([$id]); back('Yazı silindi.');
  }
  // yadd / yedit
  $title=mb_substr($P('title'),0,160); if($title==='') back('Yazı başlığı boş olamaz.');
  $slug=yazi_slug($db,$P('slug'),$title,$a==='yedit'?$id:0);
  $desc=mb_substr(preg_replace('/\s+/u',' ',$P('description')),0,300);
  $body=(string)($_POST['body']??'');
  $pub=!empty($_POST['published'])?1:0;
  $on=$P('published_on')!=='' ? dt($P('published_on')) : ($pub?date('Y-m-d'):null);
  $img=saveImg($_FILES['image']??null) ?? urlImg($P('image_url'));
  $imgMsg=((!empty($_FILES['image']['name'])||$P('image_url')!=='')&&!$img)?' Görsel alınamadı: dosya ya da link JPG, PNG veya WEBP olmalı.':'';
  if($a==='yadd'){
    $db->prepare("INSERT INTO posts(slug,title,description,body,image,published,published_on) VALUES(?,?,?,?,?,?,?)")->execute([$slug,$title,$desc,$body,$img,$pub,$on]);
    back(($pub?'Yazı yayınlandı: ':'Yazı taslak olarak kaydedildi: ').'/rehber/'.$slug.$imgMsg);
  }
  if($img){
    $r=$db->prepare("SELECT image FROM posts WHERE id=?"); $r->execute([$id]); $old=$r->fetchColumn();
    if($old) @unlink(__DIR__.'/uploads/'.basename($old));
    $db->prepare("UPDATE posts SET image=? WHERE id=?")->execute([$img,$id]);
  }
  if(!empty($_POST['image_del']) && !$img){
    $r=$db->prepare("SELECT image FROM posts WHERE id=?"); $r->execute([$id]); $old=$r->fetchColumn();
    if($old) @unlink(__DIR__.'/uploads/'.basename($old));
    $db->prepare("UPDATE posts SET image=NULL WHERE id=?")->execute([$id]);
  }
  $db->prepare("UPDATE posts SET slug=?,title=?,description=?,body=?,published=?,published_on=? WHERE id=?")->execute([$slug,$title,$desc,$body,$pub,$on,$id]);
  back('Yazı kaydedildi.'.$imgMsg);
}

/* Yazı formu: yeni yazı ($y=null) ya da düzenleme */
function yazi_form($C,$y){
  $e=fn($k)=>h((string)($y[$k]??''));
  $o='<form method="post" enctype="multipart/form-data" class="row">'.$C.'<input type="hidden" name="a" value="'.($y?'yedit':'yadd').'">'.($y?'<input type="hidden" name="id" value="'.(int)$y['id'].'">':'');
  $o.='<label class="lbl full">Başlık<input name="title" value="'.$e('title').'" maxlength="160" required placeholder="örn. Ledli İsimlik Nedir, Nasıl Yapılır?"></label>';
  $o.='<label class="lbl full">Adres (boş bırakırsanız başlıktan oluşur)<input name="slug" value="'.$e('slug').'" maxlength="70" placeholder="ledli-isimlik-nedir"></label>';
  $o.='<label class="lbl full">Google açıklaması (en fazla ~155 karakter; aramada başlığın altında görünür)<textarea name="description" rows="2" maxlength="300" data-say="155">'.$e('description').'</textarea></label>';
  $o.='<label class="lbl full">Yazı<textarea name="body" rows="18" class="mono">'.$e('body').'</textarea></label>';
  $o.='<p class="full hint">Yazım: <b>## Ara başlık</b> · <b>### Küçük başlık</b> · satır başında <b>-</b> madde, <b>1.</b> numaralı madde · <b>**kalın**</b> · <b>*italik*</b> · bağlantı: <b>[Ledli isimlik](/urun/5-ledli-isimlik)</b> · paragraflar arasında boş satır bırakın.</p>';
  $o.='<label class="lbl">Kapak görseli<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>';
  $o.='<label class="lbl">ya da görsel linki<input name="image_url" placeholder="https://..."></label>';
  if($y && $y['image']) $o.='<label class="ck"><input type="checkbox" name="image_del" value="1"> Mevcut kapak görselini kaldır</label>';
  $o.='<label class="lbl">Yayın tarihi<input type="date" name="published_on" value="'.$e('published_on').'"></label>';
  $o.='<label class="ck"><input type="checkbox" name="published" value="1"'.(!empty($y['published'])?' checked':'').'> Sitede yayınla (işaretlemezseniz taslak kalır)</label>';
  $o.='<button>'.($y?'Kaydet':'Yazıyı kaydet').'</button></form>';
  return $o;
}

function yazi_page($db,$C){
  $ys=$db->query("SELECT * FROM posts ORDER BY published DESC, COALESCE(published_on,created_at) DESC, id DESC")->fetchAll();
  $yay=count(array_filter($ys,fn($y)=>$y['published'])); ?>
<div class="cards"><div><small>Yayında</small><b><?=$yay?></b></div><div><small>Taslak</small><b><?=count($ys)-$yay?></b></div></div>
<p class="hint">Yayındaki yazılar <a href="/rehber" target="_blank" rel="noopener">mikro3datolye.com.tr/rehber</a> sayfasında listelenir, site haritasına otomatik eklenir ve sitenin menüsünde "Rehber" bağlantısı görünür. İçerik planı ve hedef kelimeler için <b>icerik-plani.md</b> dosyasına bakın.</p>
<details class="add"<?=$ys?'':' open'?>><summary>+ Yeni yazı</summary><?=yazi_form($C,null)?></details>
<div class="x"><table><tr><th>Yazı</th><th>Tarih</th><th>Durum</th><th></th></tr>
<?php foreach($ys as $y): ?><tr><td><b><?=h($y['title'])?></b><br><small>/rehber/<?=h($y['slug'])?> · <?=yaziSure($y['body'])?> dk okuma<?=$y['description']===''?' · <span class="neg">Google açıklaması yok</span>':''?></small></td>
<td class="nowrap"><?=$y['published_on']?day($y['published_on']):'—'?></td>
<td><?=$y['published']?'Yayında<br><small><a href="'.h(yaziUrl($y)).'" target="_blank" rel="noopener">Sayfayı gör</a></small>':'Taslak<br><small><a href="/rehber.php?s='.h(rawurlencode($y['slug'])).'&amp;onizle=1" target="_blank" rel="noopener">Önizle</a></small>'?></td>
<td class="act"><details class="ed"><summary>Düzenle</summary><?=yazi_form($C,$y)?></details>
<form method="post"><?=$C?><input type="hidden" name="id" value="<?=(int)$y['id']?>"><button class="alt" name="a" value="ytog"><?=$y['published']?'Taslağa al':'Yayınla'?></button>
<button class="del" name="a" value="ydel" onclick="return confirm('Bu yazı silinsin mi?')">Sil</button></form></td></tr>
<?php endforeach; if(!$ys): ?><tr><td colspan="4" class="empty">Henüz yazı yok. Yukarıdan ilk yazınızı ekleyin.</td></tr><?php endif; ?></table></div>
<script>
// Google açıklaması için karakter sayacı
document.querySelectorAll('textarea[data-say]').forEach(function(t){
  var lim=+t.dataset.say, s=document.createElement('small'); t.parentNode.appendChild(s);
  function u(){ var n=t.value.length; s.textContent=n+' / '+lim+' karakter'+(n>lim?' · Google fazlasını kesebilir':''); s.className=n>lim?'neg':''; }
  t.addEventListener('input',u); u();
});
</script>
<?php }
