<?php require 'config.php'; require 'urun-ortak.php'; require 'yazi-ortak.php';
/* Rehber yazıları. /rehber -> tüm yazılar, /rehber/ledli-isimlik-nedir -> tek yazı (.htaccess yönlendirir: rehber.php?s=...).
   Yazılar panelin "Yazılar" sekmesinden eklenir. Taslaklar yalnızca panele giriş yapmış kişiye ?onizle=1 ile görünür. */
$ig='https://www.instagram.com/mikro3datolye'; $shopier='https://www.shopier.com/mikro3datolye'; $dolap='https://link.dolap.com/1e0r7d'; $mail='info@mikro3datolye.com.tr';
$waMsg=WA.'?text='.rawurlencode('Merhaba, kişiye özel bir ürün hakkında bilgi almak istiyorum.');
date_default_timezone_set('Europe/Istanbul');
$jf=JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP;

$s=(string)($_GET['s']??''); $y=null; $liste=($s==='');
$onizle=false;
if(!$liste && isset($_GET['onizle'])){ panel_session(); $onizle=!empty($_SESSION['ok']); }

try{
  if($liste){
    $ys=$db->query("SELECT * FROM posts WHERE published=1 ORDER BY published_on DESC, id DESC")->fetchAll();
  } else {
    $st=$db->prepare("SELECT * FROM posts WHERE slug=?".($onizle?'':' AND published=1')); $st->execute([$s]); $y=$st->fetch() ?: null;
  }
}catch(Throwable $e){ $ys=[]; $y=null; }   // tablo henüz oluşmadıysa (panele hiç girilmediyse) boş gösterir

$ld=[]; $robots='';
if($liste){
  $title='Rehber: Kişiye Özel 3D Baskı Hediye Fikirleri | Mikro 3D Atölye';
  $desc='3D baskı hediyeler, ledli isimlikler, malzemeler ve özel sipariş hakkında Bursa\'daki atölyemizden rehber yazıları.';
  $canon=SITE.'/rehber';
  if(!$ys) $robots='noindex';
  $ld[]=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Ana sayfa','item'=>SITE.'/'],['@type'=>'ListItem','position'=>2,'name'=>'Rehber']]];
} elseif($y){
  $url=yaziUrl($y);
  if(!$onizle && parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)!==$url){ header('Location: '.$url,true,301); exit; }   // tek, kalıcı adres
  $title=$y['title'].' | Mikro 3D Atölye';
  $desc=$y['description']!=='' ? $y['description'] : kisalt(strip_tags(yaziHtml($y['body'])),155);
  $canon=SITE.$url;
  $img=$y['image'] ? SITE.'/uploads/'.$y['image'] : '';
  $pubOn=$y['published_on'] ?: substr((string)$y['created_at'],0,10);
  if(!$y['published']) $robots='noindex';
  $art=['@context'=>'https://schema.org','@type'=>'BlogPosting','headline'=>$y['title'],'description'=>$desc,'inLanguage'=>'tr',
    'datePublished'=>$pubOn,'dateModified'=>date('c',strtotime((string)$y['updated_at'])),'mainEntityOfPage'=>$canon,
    'author'=>['@type'=>'Organization','name'=>'Mikro 3D Atölye','url'=>SITE.'/'],
    'publisher'=>['@type'=>'Organization','name'=>'Mikro 3D Atölye','url'=>SITE.'/','logo'=>['@type'=>'ImageObject','url'=>SITE.'/app-icon.php?s=512']]];
  if($img) $art['image']=[$img];
  $ld[]=$art;
  $ld[]=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Ana sayfa','item'=>SITE.'/'],['@type'=>'ListItem','position'=>2,'name'=>'Rehber','item'=>SITE.'/rehber'],
    ['@type'=>'ListItem','position'=>3,'name'=>$y['title']]]];
  // Yazının altında: diğer yazılar ve ürünler (iç bağlantı)
  $r=$db->prepare("SELECT * FROM posts WHERE published=1 AND id<>? ORDER BY published_on DESC, id DESC LIMIT 3"); $r->execute([$y['id']]); $digerY=$r->fetchAll();
  $urunler=$db->query("SELECT * FROM products WHERE active=1 ORDER BY id DESC LIMIT 4")->fetchAll();
} else {
  http_response_code(404); $title='Yazı bulunamadı | Mikro 3D Atölye'; $desc=''; $canon=''; $robots='noindex';
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script>
/* Analiz çerezleri yalnızca ziyaretçi kabul ederse yüklenir. */
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
function loadAnalytics(){
  if (window.__gaLoaded) return; window.__gaLoaded = true;
  var s = document.createElement('script'); s.async = true;
  s.src = 'https://www.googletagmanager.com/gtag/js?id=G-DM8M8T5C9S';
  document.head.appendChild(s);
  gtag('js', new Date()); gtag('config', 'G-DM8M8T5C9S');
}
</script>
<title><?= h($title) ?></title>
<?php if($robots): ?><meta name="robots" content="<?= $robots ?>"><?php endif; ?>
<?php if($desc!==''): ?><meta name="description" content="<?= h($desc) ?>"><?php endif; ?>
<?php if($canon): ?><link rel="canonical" href="<?= h($canon) ?>">
<meta property="og:type" content="<?= $y?'article':'website' ?>"><meta property="og:site_name" content="Mikro 3D Atölye"><meta property="og:locale" content="tr_TR">
<meta property="og:title" content="<?= h($y?$y['title']:'Rehber · Mikro 3D Atölye') ?>"><meta property="og:description" content="<?= h($desc) ?>"><meta property="og:url" content="<?= h($canon) ?>">
<?php if(!empty($img)): ?><meta property="og:image" content="<?= h($img) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
<?php if($y): ?><meta property="article:published_time" content="<?= h($pubOn) ?>"><?php endif; ?>
<?php endif; ?>
<?php foreach($ld as $j): ?><script type="application/ld+json"><?= json_encode($j,$jf) ?></script>
<?php endforeach; ?>
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#1b1931" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f5edda" media="(prefers-color-scheme: light)">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/app-icon.php?s=180&amp;v=2">
<link rel="preload" href="/barlow-condensed-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<script>(function(){var d=document.documentElement,K="m3d_tema",C={dark:"#1b1931",light:"#f5edda"};function sys(){return window.matchMedia&&matchMedia("(prefers-color-scheme: light)").matches?"light":"dark"}function bar(){var t=d.getAttribute("data-theme"),m=document.querySelectorAll("meta[name=theme-color]");for(var i=0;i<m.length;i++)m[i].content=t?C[t]:(/light/.test(m[i].media)?C.light:C.dark)}var s=null;try{s=localStorage.getItem(K)}catch(e){}if(s==="light"||s==="dark"){d.setAttribute("data-theme",s);bar()}window.temaDegis=function(){var n=(d.getAttribute("data-theme")||sys())==="light"?"dark":"light";try{if(n===sys()){d.removeAttribute("data-theme");localStorage.removeItem(K)}else{d.setAttribute("data-theme",n);localStorage.setItem(K,n)}}catch(e){d.setAttribute("data-theme",n)}bar()}})();</script>
<link rel="stylesheet" href="/site.css?v=11"></head><body>

<header class="top"><div class="wrap">
  <a class="logo" href="/" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"></span><span><b>Mikro 3D Atölye</b><small>Kişiye özel 3D baskı</small></span></a>
  <nav id="menu" aria-label="Ana menü"><a href="/">Ana sayfa</a><a href="/#urunler">Ürünler</a><a href="/#ozel">Özel sipariş</a><a href="/rehber"<?= $liste?' aria-current="page"':'' ?>>Rehber</a><a href="/#sss">SSS</a><a href="/#iletisim">İletişim</a><a class="btn sm" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></nav>
  <button class="theme-btn" type="button" onclick="temaDegis()" aria-label="Açık ya da koyu temaya geç" title="Açık / koyu tema"><svg class="i-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg><svg class="i-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg></button>
  <button class="menu-btn" type="button" aria-expanded="false" aria-controls="menu" aria-label="Menüyü aç" onclick="var t=this.parentNode.parentNode,o=t.classList.toggle('open');this.setAttribute('aria-expanded',o?'true':'false')"><span></span><span></span><span></span></button>
</div></header>

<main class="pd"><div class="wrap">
<?php if($liste): ?>
  <nav class="crumbs" aria-label="Bulunduğunuz yer"><a href="/">Ana sayfa</a> / Rehber</nav>
  <header class="post-head"><span class="cat">Atölyeden notlar</span><h1>Rehber</h1>
    <p class="lead">Kişiye özel 3D baskı hediyeler, malzemeler ve özel sipariş süreci hakkında bilmeniz gerekenler.</p></header>
  <?php if($ys): ?><div class="grid"><?php foreach($ys as $x) echo yaziKart($x); ?></div>
  <?php else: ?><div class="empty"><b>Yazılar hazırlanıyor.</b><p>Bu arada ürünlerimize göz atabilir ya da aklınızdaki ürün için bize yazabilirsiniz.</p>
    <div class="acts"><a class="btn" href="/#urunler">Ürünler</a><a class="btn ghost" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></div></div><?php endif; ?>
<?php elseif($y): ?>
  <?php if($onizle && !$y['published']): ?><p class="draft-note">Önizleme: bu yazı taslak, ziyaretçiler göremez.</p><?php endif; ?>
  <nav class="crumbs" aria-label="Bulunduğunuz yer"><a href="/">Ana sayfa</a> / <a href="/rehber">Rehber</a></nav>
  <article class="post">
    <header class="post-head"><span class="cat">Rehber · <?= yaziSure($y['body']) ?> dk okuma</span><h1><?= h($y['title']) ?></h1>
      <?php if($y['description']!==''): ?><p class="lead"><?= h($y['description']) ?></p><?php endif; ?>
      <p class="meta"><time datetime="<?= h($pubOn) ?>"><?= h(tarihTr($pubOn)) ?></time> · Mikro 3D Atölye, Bursa</p></header>
    <?php if($y['image']): ?><figure class="post-pic"><img src="/uploads/<?= h($y['image']) ?>" alt="<?= h($y['title']) ?>" fetchpriority="high"></figure><?php endif; ?>
    <div class="post-body"><?= yaziHtml($y['body']) ?></div>
    <aside class="post-cta"><b>Aklınızda bir ürün mü var?</b><p>Fotoğraf, ölçü ya da sadece fikrinizi gönderin; Bursa'daki atölyemizde sizin için basalım.</p>
      <div class="acts"><a class="btn" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a><a class="btn ghost" href="/#urunler">Ürünlere bak</a></div></aside>
  </article>
  <?php if($urunler): ?><section class="pd-more" aria-labelledby="urn"><h2 id="urn">Atölyeden ürünler</h2><div class="grid"><?php foreach($urunler as $m) echo urunKart($m); ?></div></section><?php endif; ?>
  <?php if($digerY): ?><section class="pd-more" aria-labelledby="dy"><h2 id="dy">Diğer yazılar</h2><div class="grid"><?php foreach($digerY as $x) echo yaziKart($x); ?></div></section><?php endif; ?>
<?php else: ?>
  <div class="empty"><b>Bu yazı bulunamadı.</b><p>Yazı kaldırılmış ya da adres yanlış yazılmış olabilir.</p>
    <div class="acts"><a class="btn" href="/rehber">Tüm yazılar</a><a class="btn ghost" href="/#urunler">Ürünler</a></div></div>
<?php endif; ?>
</div></main>

<footer><div class="wrap">
  <h2>Aklınızdaki ürünü birlikte tasarlayalım.</h2>
  <a class="btn light" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yazın</a>
  <ul class="links" aria-label="İletişim ve mağazalar">
    <li><a href="<?= $ig ?>" rel="noopener">Instagram</a></li>
    <li><a href="<?= $shopier ?>" rel="noopener">Shopier</a></li>
    <li><a href="<?= $dolap ?>" rel="noopener">Dolap</a></li>
    <li><a href="mailto:<?= $mail ?>"><?= $mail ?></a></li>
  </ul>
  <p class="copy">© <?= date('Y') ?> Mikro 3D Atölye · <button type="button" class="linkbtn" id="consent-open">Çerez tercihi</button> · <a href="/gizlilik.php">Gizlilik ve çerezler</a></p>
</div></footer>

<div class="consent" id="consent" role="region" aria-label="Çerez tercihi" hidden>
  <p>Bu site, ziyaretlerin nasıl kullanıldığını anlamak için Google Analytics çerezleri kullanır. Siz kabul etmeden analiz çerezleri çalışmaz. Tercihinizi sayfanın altındaki "Çerez tercihi" bağlantısından değiştirebilirsiniz. <a href="/gizlilik.php">Ayrıntılı bilgi</a></p>
  <div class="acts"><button type="button" class="btn sm" id="consent-yes">Kabul et</button><button type="button" class="btn sm ghost" id="consent-no">Reddet</button></div>
</div>
<script>
(function(){
  var KEY='m3d_cerez', box=document.getElementById('consent');
  function get(){ try{ return localStorage.getItem(KEY); }catch(e){ return null; } }
  function set(v){ try{ localStorage.setItem(KEY,v); }catch(e){} }
  function apply(v){ if(v==='evet') loadAnalytics(); box.hidden = (v==='evet'||v==='hayir'); }
  document.getElementById('consent-yes').onclick=function(){ set('evet'); apply('evet'); };
  document.getElementById('consent-no').onclick=function(){ set('hayir'); apply('hayir'); };
  var re=document.getElementById('consent-open');
  if(re) re.onclick=function(){ box.hidden=false; box.querySelector('button').focus(); };
  apply(get());
})();
</script>
</body></html>
