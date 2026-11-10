<?php require 'config.php'; require 'urun-ortak.php';
/* Tek ürün sayfası. Adres: /urun/12-urun-adi (.htaccess bu adresi urun.php?id=12'ye yönlendirir). */
$ig='https://www.instagram.com/mikro3datolye'; $shopier='https://www.shopier.com/mikro3datolye'; $dolap='https://link.dolap.com/1e0r7d'; $mail='info@mikro3datolye.com.tr';
$waMsg=WA.'?text='.rawurlencode('Merhaba, kişiye özel bir ürün hakkında bilgi almak istiyorum.');

$st=$db->prepare("SELECT * FROM products WHERE id=? AND active=1"); $st->execute([(int)($_GET['id']??0)]); $p=$st->fetch();
if($p){
  $url=urunUrl($p);
  // Eski ya da yanlış yazılmış adresleri tek, kalıcı adrese yönlendirir (Google aynı ürünü iki kez saymasın)
  if(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)!==$url){ header('Location: '.$url,true,301); exit; }
  $r=$db->prepare("SELECT * FROM products WHERE active=1 AND id<>? ORDER BY (category=?) DESC, id DESC LIMIT 4"); $r->execute([$p['id'],$p['category']]); $more=$r->fetchAll();
  $title=$p['name'].' | Mikro 3D Atölye';
  $desc=$p['description'] ? kisalt($p['description'],155) : $p['name'].' · '.tl($p['price']).'. Mikro 3D Atölye\'de 3D baskıyla üretilir; renk ve kişiselleştirme için bize yazın.';
  $img=$p['image'] ? SITE.'/uploads/'.$p['image'] : '';
  $ld=['@context'=>'https://schema.org','@type'=>'Product','name'=>$p['name'],'description'=>$p['description']?:$desc,'category'=>$p['category'],
       'brand'=>['@type'=>'Brand','name'=>'Mikro 3D Atölye'],
       'offers'=>['@type'=>'Offer','price'=>number_format((float)$p['price'],2,'.',''),'priceCurrency'=>'TRY','availability'=>'https://schema.org/InStock','url'=>SITE.$url]];
  if($img) $ld['image']=[$img];
} else {
  http_response_code(404); $title='Ürün bulunamadı | Mikro 3D Atölye'; $desc='';
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
<?php if($p): ?>
<meta name="description" content="<?= h($desc) ?>">
<link rel="canonical" href="<?= h(SITE.$url) ?>">
<meta property="og:type" content="product"><meta property="og:site_name" content="Mikro 3D Atölye"><meta property="og:locale" content="tr_TR">
<meta property="og:title" content="<?= h($p['name']) ?>"><meta property="og:description" content="<?= h($desc) ?>"><meta property="og:url" content="<?= h(SITE.$url) ?>">
<?php if($img): ?><meta property="og:image" content="<?= h($img) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
<meta property="product:price:amount" content="<?= h(number_format((float)$p['price'],2,'.','')) ?>"><meta property="product:price:currency" content="TRY">
<script type="application/ld+json"><?= json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP) ?></script>
<?php else: ?><meta name="robots" content="noindex"><?php endif; ?>
<meta name="theme-color" content="#1b1931">
<link rel="preload" href="/barlow-condensed-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/site.css?v=7"></head><body>

<header class="top"><div class="wrap">
  <a class="logo" href="/" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"><i></i><i></i><i></i></span><span><b>Mikro 3D Atölye</b><small>Kişiye özel 3D baskı</small></span></a>
  <nav id="menu" aria-label="Ana menü"><a href="/">Ana sayfa</a><a href="/#urunler">Ürünler</a><a href="/#ozel">Özel sipariş</a><a href="/#atolye">Atölye</a><a href="/#sss">SSS</a><a href="/#iletisim">İletişim</a><a class="btn sm" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></nav>
  <button class="menu-btn" type="button" aria-expanded="false" aria-controls="menu" aria-label="Menüyü aç" onclick="var t=this.parentNode.parentNode,o=t.classList.toggle('open');this.setAttribute('aria-expanded',o?'true':'false')"><span></span><span></span><span></span></button>
</div></header>

<main class="pd"><div class="wrap">
<?php if($p): ?>
  <nav class="crumbs" aria-label="Bulunduğunuz yer"><a href="/">Ana sayfa</a> / <a href="/#urunler">Ürünler</a> / <a href="/?k=<?= urlencode($p['category']) ?>#urunler"><?= h($p['category']) ?></a></nav>
  <div class="pd-grid">
    <div class="pic pd-pic"><?php if($p['image']): ?><img src="/uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>"><?php else: ?><span class="ph" aria-hidden="true"></span><?php endif; ?></div>
    <div class="pd-info">
      <span class="cat"><?= h($p['category']) ?></span>
      <h1><?= h($p['name']) ?></h1>
      <p class="price"><?= tl($p['price']) ?></p>
      <?php if($p['description']): ?><div class="pd-desc"><p><?= nl2br(h(trim($p['description']))) ?></p></div><?php endif; ?>
      <div class="acts">
        <?php if($p['shopier_url']): ?><a class="btn" href="<?= h($p['shopier_url']) ?>" rel="noopener">Shopier'den satın al</a><?php endif; ?>
        <a class="btn<?= $p['shopier_url']?' ghost':'' ?>" href="<?= h(urunMesaj($p)) ?>" rel="noopener">WhatsApp'tan sor</a>
        <button type="button" class="btn ghost" id="share">Paylaş</button>
      </div>
      <ul class="pd-notes">
        <li style="--c:var(--coral)">Ürüne uygunsa isim, renk ve ölçüyü siz seçersiniz. Ayrıntılar için WhatsApp'tan yazın.</li>
        <li style="--c:var(--cyan)">Renk seçenekleri atölyedeki güncel filament stoğuna göre değişir.</li>
        <li style="--c:var(--lime)">Ürünler atölyemizde 3D baskıyla üretilir.</li>
      </ul>
    </div>
  </div>
  <?php if($more): ?><section class="pd-more" aria-labelledby="benzer"><h2 id="benzer">Diğer ürünler</h2><div class="grid"><?php foreach($more as $m) echo urunKart($m); ?></div></section><?php endif; ?>
<?php else: ?>
  <div class="empty"><b>Bu ürün bulunamadı.</b><p>Ürün satıştan kaldırılmış ya da adres yanlış yazılmış olabilir.</p>
    <div class="acts"><a class="btn" href="/#urunler">Tüm ürünler</a><a class="btn ghost" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></div></div>
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
  // Paylaş: telefonda paylaşım menüsünü açar, bilgisayarda linki kopyalar
  var sb=document.getElementById('share');
  if(sb) sb.onclick=function(){
    var d={title:document.title,url:location.href};
    if(navigator.share){ navigator.share(d).catch(function(){}); return; }
    if(navigator.clipboard){ navigator.clipboard.writeText(location.href).then(function(){ sb.textContent='Link kopyalandı'; }); }
  };
})();
</script>
</body></html>
