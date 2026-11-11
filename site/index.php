<?php require 'config.php'; require 'urun-ortak.php';
/* Ana sayfa. Tasarım: Google Stitch taslağından uyarlandı; ürünler, kategoriler ve fotoğraflar panelden gelir. */
$wa     = WA;
$waMsg  = $wa . '?text=' . rawurlencode('Merhaba, kişiye özel bir ürün hakkında bilgi almak istiyorum.');
$ig     = 'https://www.instagram.com/mikro3datolye';
$shopier= 'https://www.shopier.com/mikro3datolye';
$dolap  = 'https://link.dolap.com/1e0r7d';
$mail   = 'info@mikro3datolye.com.tr';
$tel    = '0534 666 81 02';
$cats = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$c = $_GET['k'] ?? '';
$st = $c !== '' ? $db->prepare("SELECT * FROM products WHERE active=1 AND category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM products WHERE active=1 AND ?='' ORDER BY id DESC");
$st->execute([$c]); $items = $st->fetchAll();
// Fotoğrafı olan ürünler (en yeniden eskiye): giriş, galeri ve alt bilgi fotoğrafları buradan gelir
$pics = $db->query("SELECT * FROM products WHERE active=1 AND image IS NOT NULL AND image<>'' ORDER BY id DESC")->fetchAll();
$catImg = []; foreach($pics as $p) if(!isset($catImg[$p['category']])) $catImg[$p['category']] = $p['image'];
$catNum = $db->query("SELECT category, COUNT(*) FROM products WHERE active=1 GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);
$aboutPic = $pics[2] ?? $pics[0] ?? null;

// --- SEO: başlık, açıklama, paylaşım görseli ve yapısal veri ---
$seoTitle = 'Kişiye Özel 3D Baskı Hediyelik ve Dekorasyon | Mikro 3D Atölye';
$seoDesc  = 'Bursa\'da butik 3D baskı atölyesi: isme özel ledli isimlikler, hediyelikler ve ev dekorasyonu. Fikrinizi WhatsApp\'tan yazın, birlikte üretelim.';
$seoImg   = $pics ? SITE.'/uploads/'.$pics[0]['image'] : '';
$all = $c === '' ? $items : $db->query("SELECT * FROM products WHERE active=1 ORDER BY id DESC")->fetchAll();
$ld = [
  ['@context'=>'https://schema.org','@type'=>'OnlineStore','@id'=>SITE.'/#atolye','name'=>'Mikro 3D Atölye','url'=>SITE.'/',
   'description'=>$seoDesc,'email'=>$mail,'telephone'=>'+905346668102',
   'sameAs'=>[$ig,$shopier],
   'address'=>['@type'=>'PostalAddress','addressLocality'=>'Bursa','addressCountry'=>'TR'],
   'contactPoint'=>['@type'=>'ContactPoint','contactType'=>'customer service','telephone'=>'+905346668102','email'=>$mail,'availableLanguage'=>'Turkish']]
   + ($seoImg ? ['image'=>$seoImg] : []),
  ['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'Mikro 3D Atölye','alternateName'=>'mikro3datolye','url'=>SITE.'/','inLanguage'=>'tr','publisher'=>['@id'=>SITE.'/#atolye']],
];
if($all){
  $li=[]; foreach($all as $i=>$p) $li[]=['@type'=>'ListItem','position'=>$i+1,'name'=>$p['name'],'url'=>SITE.urunUrl($p)];
  $ld[]=['@context'=>'https://schema.org','@type'=>'ItemList','name'=>'Mikro 3D Atölye ürünleri','itemListElement'=>$li];
}

function ic($n){
  static $I = [
    'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>',
    'ne'=>'<path d="M7 17L17 7M9 7h8v8"/>',
    'chat'=>'<path d="M20 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20l1.2-4.2A8.5 8.5 0 1 1 20 11.5z"/>',
    'star'=>'<path d="M12 3l2.6 5.6 6 .7-4.5 4.1 1.2 6L12 16.4 6.7 19.4l1.2-6-4.5-4.1 6-.7z"/>',
    'gift'=>'<rect x="3" y="8" width="18" height="13" rx="1.5"/><path d="M3 12h18M12 8v13M12 8c-1.6-3.2-5.4-4-6.2-2s2.2 2 6.2 2zm0 0c1.6-3.2 5.4-4 6.2-2s-2.2 2-6.2 2z"/>',
    'tag'=>'<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.4"/>',
    'drop'=>'<path d="M12 3c3.2 4.2 6 7.6 6 11a6 6 0 0 1-12 0c0-3.4 2.8-6.8 6-11z"/>',
    'ruler'=>'<path d="M3 17L17 3l4 4L7 21z"/><path d="M7.5 12.5l2 2M10.5 9.5l2 2M13.5 6.5l2 2"/>',
    'printer'=>'<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M4 8h16M10 8v3l2 2 2-2V8M8 17.5h8"/>',
    'layers'=>'<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
    'spool'=>'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/>',
    'phone'=>'<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
    'mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    'insta'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8"/>',
    'bulb'=>'<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3z"/>',
    'home'=>'<path d="M4 11l8-7 8 7v9a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1z"/>',
    'pen'=>'<path d="M4 20l4-1 11-11-3-3L5 16z"/><path d="M14 6l3 3"/>',
    'users'=>'<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
    'send'=>'<path d="M21 3L3 10.5l7 2.5 2.5 7z"/><path d="M10 13L21 3"/>',
  ];
  return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">'.($I[$n]??'').'</svg>';
}
function pola($p,$cls='',$tag='',$eager=false){
  return '<a class="pola '.$cls.'" href="'.h(urunUrl($p)).'"><img src="uploads/'.h($p['image']).'" alt="'.h($p['name']).'" '.($eager?'fetchpriority="high"':'loading="lazy"').'><span class="cap"><span>'.h($p['name']).'</span>'.($tag!==''?'<span class="tag dark">'.$tag.'</span>':'').'</span></a>';
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script>
document.documentElement.className='js';
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
<title><?= h($seoTitle) ?></title>
<meta name="description" content="<?= h($seoDesc) ?>">
<link rel="canonical" href="<?= SITE ?>/">
<meta property="og:type" content="website"><meta property="og:site_name" content="Mikro 3D Atölye"><meta property="og:locale" content="tr_TR">
<meta property="og:title" content="<?= h($seoTitle) ?>"><meta property="og:description" content="<?= h($seoDesc) ?>"><meta property="og:url" content="<?= SITE ?>/">
<?php if($seoImg): ?><meta property="og:image" content="<?= h($seoImg) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
<script type="application/ld+json"><?= json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP) ?></script>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/app-icon.php?s=180&amp;v=2">
<meta name="theme-color" content="#1b1931">
<link rel="preload" href="barlow-condensed-latin-800-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="site.css?v=8"></head><body>

<header class="top" id="top"><div class="wrap">
  <a class="logo" href="./" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"></span><span><b>Mikro 3D Atölye</b><small>Kişiye özel 3D baskı</small></span></a>
  <nav id="menu" aria-label="Ana menü"><a href="#top" aria-current="page">Ana sayfa</a><a href="#urunler">Ürünler</a><a href="#ozel">Özel sipariş</a><a href="#atolye">Atölye</a><a href="#sss">SSS</a><a href="#iletisim">İletişim</a><a class="btn sm" href="<?= h($waMsg) ?>" rel="noopener"><?= ic('chat') ?>WhatsApp'tan yaz</a></nav>
  <button class="menu-btn" type="button" aria-expanded="false" aria-controls="menu" aria-label="Menüyü aç"><span></span><span></span><span></span></button>
</div></header>

<main>
<section class="hero torn-b"><div class="wrap">
  <div class="hero-txt">
    <div class="hero-kick"><span class="pill"><?= ic('star') ?>Bursa'da butik 3D baskı atölyesi</span><span class="eyebrow">Fikirden ürüne</span></div>
    <h1>Bir fikirle başlar,<br> <span class="hl">3D baskıyla</span><br> gerçeğe dönüşür.</h1>
    <p class="sub">Kişiye özel hediyelikler, özgün ev dekorasyonu ve günlük hayatı kolaylaştıran tasarımlar üreten butik bir 3D baskı atölyesi. Fikrinizi birlikte ürüne dönüştürelim.</p>
    <div class="acts"><a class="btn berry" href="#urunler">Ürünlere bak <?= ic('arrow') ?></a><a class="btn dark" href="#ozel"><?= ic('pen') ?>Özel tasarım iste</a></div>
    <ul class="stats"><li><b>İsme özel</b><small>Kişisel dokunuş</small></li><li><b>Renk sizden</b><small>Stoktaki renklerden</small></li><li><b>Atölyede</b><small>Kendi üretimimiz</small></li></ul>
  </div>
  <?php if($pics): ?><div class="hero-art">
    <span class="float-badge"><span class="tag sun">Kişiye özel</span></span>
    <?= pola($pics[0],'p'.(isset($pics[1])?'1':'2'),tl($pics[0]['price']),true) ?>
    <?php if(isset($pics[1])) echo pola($pics[1],'p2',tl($pics[1]['price'])); ?>
  </div><?php endif; ?>
</div></section>

<div class="marquee" aria-hidden="true"><div class="track"><?php for($r=0;$r<2;$r++): foreach([['tag','İsme özel tasarım'],['bulb','Ledli isimlik'],['drop','Renk sizden'],['printer','Atölyede basılır'],['layers','PLA • PETG • TPU']] as [$i,$w]): ?><span><?= ic($i) ?><?= h($w) ?></span><i>•</i><?php endforeach; endfor; ?></div></div>

<section class="sec about" id="atolye"><div class="wrap">
  <div class="sec-head center"><span class="eyebrow">Hakkımızda</span><h2>Her ürün, bir fikirden doğar</h2><p class="lead">Mikro 3D Atölye, Bursa'da butik bir 3D baskı atölyesi. Ürünleri FDM yöntemiyle, Creality Ender 3 V3 CoreXZ yazıcıda basıyoruz. Hazır modelleri ihtiyacınıza göre uyarlıyor, gerektiğinde yeni tasarımlar hazırlıyoruz.</p></div>
  <div class="about-grid">
    <div class="about-col">
      <article class="feat"><span class="ib b-sun"><?= ic('gift') ?></span><h3 class="c-sun">Kişiye özel hediyelikler</h3><p>İsimli objeler, bebek ve çocuk odası süsleri, masa süsleri ve özel günler için hediyelikler.</p></article>
      <article class="feat"><span class="ib b-cyan"><?= ic('bulb') ?></span><h3 class="c-cyan">Aydınlatma</h3><p>Ledli isimlikler, masa lambaları ve geometrik desenli lamba gövdeleri.</p></article>
    </div>
    <?php if($aboutPic): ?><figure class="about-pic"><img src="uploads/<?= h($aboutPic['image']) ?>" alt="<?= h($aboutPic['name']) ?>" loading="lazy"><figcaption><span><?= h($aboutPic['name']) ?></span><span class="tag dark">Atölyeden</span></figcaption></figure><?php endif; ?>
    <div class="about-col">
      <article class="feat"><span class="ib b-berry"><?= ic('home') ?></span><h3 class="c-pink">Ev dekorasyonu</h3><p>Vazolar, figürler, raf ve masa süsleri; masaüstü düzenleyiciler ve kullanışlı aparatlar.</p></article>
      <article class="feat"><span class="ib b-cream"><?= ic('pen') ?></span><h3 class="c-y">Özel tasarım</h3><p>Fotoğraf, ölçü ya da sadece bir fikir paylaşın. Uygun projelerde mevcut modelleri uyarlıyor, basit tasarımlar hazırlıyoruz.</p></article>
    </div>
  </div>
  <div class="center mt"><a class="btn dark" href="#ozel">Özel sipariş nasıl işler? <?= ic('arrow') ?></a></div>
</div></section>

<?php if($cats): ?><section class="sec cats-sec"><div class="wrap">
  <div class="sec-head split"><div><span class="eyebrow">Kategoriler</span><h2>Ne arıyorsunuz?</h2></div><a class="btn dark" href="#urunler">Tüm ürünleri gör <?= ic('ne') ?></a></div>
  <div class="cat-grid"><?php $cc=[['c-sun','b-sun'],['c-cyan','b-cyan'],['c-pink','b-berry']]; foreach($cats as $i=>$k): [$tc,$bc]=$cc[$i%3]; ?>
    <a class="cat-tile" href="?k=<?= urlencode($k) ?>#urunler"><span class="cimg"><?php if(isset($catImg[$k])): ?><img src="uploads/<?= h($catImg[$k]) ?>" alt="" loading="lazy"><?php else: ?><span class="ph"></span><?php endif; ?></span><div class="cbody"><div><h3 class="<?= $tc ?>"><?= h($k) ?></h3><p><?= (int)($catNum[$k]??0) ?> ürün</p></div><span class="go <?= $bc ?>"><?= ic('arrow') ?></span></div></a>
  <?php endforeach; ?></div>
</div></section><?php endif; ?>

<section class="shop torn-t torn-b" id="urunler"><div class="wrap">
  <div class="sec-head split"><div><span class="eyebrow" style="color:var(--pink)">Ürünlerimiz</span><h2>Atölyeden çıkan favoriler</h2></div>
  <?php if($cats): ?><nav class="pills" aria-label="Kategoriler"><a href="./#urunler"<?= $c===''?' aria-current="page"':'' ?>>Tümü</a><?php foreach($cats as $k): ?><a href="?k=<?= urlencode($k) ?>#urunler"<?= $c===$k?' aria-current="page"':'' ?>><?= h($k) ?></a><?php endforeach; ?></nav><?php endif; ?></div>
  <p class="stores">Ürünlerimizi <a href="<?= $shopier ?>" rel="noopener">Shopier</a> ve <a href="<?= $dolap ?>" rel="noopener">Dolap</a> mağazalarımızda da bulabilirsiniz.</p>
  <?php if(!$items): ?><div class="empty"><b>Ürünler yakında burada.</b><p>Şimdilik Shopier ve Dolap mağazalarımıza göz atabilir ya da aklınızdaki ürünü bize yazabilirsiniz.</p>
    <div class="acts"><a class="btn" href="<?= $shopier ?>" rel="noopener">Shopier mağazası</a><a class="btn dark" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></div></div><?php endif; ?>
  <div class="grid"><?php foreach($items as $p) echo urunKart($p); ?></div>
</div></section>

<section class="sec why"><div class="wrap">
  <div class="sec-head center"><span class="eyebrow">Neden biz?</span><h2>Küçük atölye, büyük özen</h2><p class="lead">Seri üretim değil: her ürün atölyemizde, sizin için basılıyor.</p></div>
  <div class="why-grid">
    <?php foreach([
      ['tag','İsme özel','Ürüne uygunsa isim, tarih ya da kısa bir yazı işlenir.','c-sun'],
      ['drop','Renk seçimi','Renkler atölyedeki güncel filament stoğundan seçilir.','c-y'],
      ['ruler','Ölçüye göre','Fotoğraf ya da ölçü gönderin, ürünü alanınıza göre uyarlayalım.','c-cyan'],
      ['printer','Atölyede üretim','Her ürün kendi atölyemizde 3D yazıcıyla basılır.','c-pink'],
      ['layers','Malzeme seçimi','Kullanım amacına göre PLA, PLA+, PETG, TPU ya da teknik filamentler.','c-sun'],
      ['chat','Doğrudan iletişim','WhatsApp’tan yazın, ürünü doğrudan üreticisiyle konuşun.','c-cyan'],
      ['users','Toplu sipariş','Toplu alımlarda fiyat için bize yazabilirsiniz.','c-y'],
      ['pen','Özel tasarım','Hazır modeliniz olmasa da olur; fikrinizden yola çıkarız.','c-pink'],
    ] as [$i,$t,$d,$cl]): ?><article><span class="ib"><?= ic($i) ?></span><h3 class="<?= $cl ?>"><?= $t ?></h3><p><?= $d ?></p></article><?php endforeach; ?>
  </div>
</div></section>

<section class="sec steps" id="ozel"><div class="wrap">
  <div class="sec-head center"><span class="eyebrow">Özel sipariş</span><h2>3 adımda ürününüz hazır</h2><p class="lead">Aklınızda bir ürün mü var? Süreç şöyle işliyor:</p></div>
  <ol>
    <li><span class="n" style="color:var(--sun)" aria-hidden="true">1</span><span class="tag sun">Adım 01</span><h3>Fikrinizi anlatın</h3><p>WhatsApp'tan fotoğraf, ölçü ya da sadece fikrinizi yazın. Hazır 3D modeliniz olması gerekmiyor.</p></li>
    <li><span class="n" style="color:var(--cyan)" aria-hidden="true">2</span><span class="tag cyan">Adım 02</span><h3>Birlikte netleştirelim</h3><p>Ürünün uygunluğuna, malzemeye ve rengine birlikte karar veririz. Fiyat ve ayrıntıları da burada konuşuruz.</p></li>
    <li><span class="n" style="color:var(--pink)" aria-hidden="true">3</span><span class="tag berry">Adım 03</span><h3>Atölyede basılsın</h3><p>Onayınızdan sonra ürün atölyede basılır. Üretim genellikle 2-3 iş günü sürer; teslimat ayrıntılarını birlikte netleştiririz.</p></li>
  </ol>
  <div class="strip"><div><?= ic('chat') ?><div><h3>Fiyat bilgisi alın</h3><p>Fikrinizi, fotoğrafınızı ya da ölçünüzü gönderin; fiyat ve ayrıntıları size iletelim.</p></div></div><a class="btn" href="<?= h($waMsg) ?>" rel="noopener"><?= ic('chat') ?>WhatsApp'tan yaz</a></div>
</div></section>

<section class="gallery torn-t torn-b" id="galeri"><div class="wrap">
  <div class="sec-head center"><span class="eyebrow">Atölyeden</span><h2>Baskıdan sonra</h2><p class="lead">Atölyeden çıkan ürünlerden kareler.</p></div>
  <?php if($pics): ?><div class="gal"><?php foreach(array_slice($pics,0,3) as $p) echo pola($p,'',''); ?></div><?php endif; ?>
  <div class="center mt"><a class="btn ink" href="<?= $ig ?>" rel="noopener"><?= ic('insta') ?>Instagram'da daha fazlası: @mikro3datolye</a></div>
  <div class="insta-gate" id="insta-gate">
    <p>Instagram paylaşımlarını burada göstermek için Instagram'ın kendi kodu yüklenir ve Instagram çerez kullanabilir. Düğmeye basana kadar hiçbir şey yüklenmez.</p>
    <button type="button" class="btn sm ink" id="insta-show">Paylaşımları göster</button>
  </div>
  <div class="insta-grid" id="insta-grid" hidden>
    <?php foreach(['DfUW1MQEdQu','DfNewD3EVX3','DfCdVeEDcUP','De7Tb1gEcvv','DepeuPOEb8x','DehiU-LERYV'] as $pid): ?><blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/<?= $pid ?>/" data-instgrm-version="14"><a href="https://www.instagram.com/p/<?= $pid ?>/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="sec contact" id="iletisim"><div class="wrap">
  <div>
    <span class="eyebrow">İletişim</span>
    <h2>Bir fikriniz mi var? Yazın.</h2>
    <p class="lead">Bir fotoğraf, ölçü ya da sadece bir cümle yeterli. Fikrinizin 3D baskıya uygun olup olmadığına birlikte bakalım.</p>
    <div class="c-list">
      <a class="c-row" href="<?= h($waMsg) ?>" rel="noopener"><span class="ib b-sun"><?= ic('chat') ?></span><span><small>WhatsApp</small><b class="c-sun"><?= $tel ?></b></span></a>
      <a class="c-row" href="mailto:<?= $mail ?>"><span class="ib b-cyan"><?= ic('mail') ?></span><span><small>E-posta</small><b class="c-cyan"><?= $mail ?></b></span></a>
      <a class="c-row" href="<?= $ig ?>" rel="noopener"><span class="ib b-berry"><?= ic('insta') ?></span><span><small>Instagram</small><b class="c-pink">@mikro3datolye</b></span></a>
    </div>
  </div>
  <form class="form-panel" id="waform" action="<?= h($wa) ?>" method="get" target="_blank" rel="noopener">
    <h3>Hemen mesaj bırakın</h3>
    <p class="lead2">Mesajınız WhatsApp'ta hazır olarak açılır; göndermeden önce düzenleyebilirsiniz.</p>
    <label>Adınız<input name="ad" autocomplete="name" placeholder="Örn: Elif"></label>
    <label>Ne yaptırmak istiyorsunuz?<textarea name="text" required placeholder="Ürün, boyut, renk, isim ya da özel notlarınız..."></textarea></label>
    <button class="btn" type="submit">WhatsApp'ta gönder <?= ic('send') ?></button>
    <p class="note">Fotoğraf ya da ölçüleri WhatsApp'ta mesaja ekleyebilirsiniz.</p>
  </form>
</div></section>

<section class="sec faq-sec" id="sss"><div class="wrap">
  <div class="sec-head center"><span class="eyebrow">SSS</span><h2>Sıkça sorulan sorular</h2><p class="lead">Kişiselleştirme, malzeme ve teslimatla ilgili merak edilenler.</p></div>
  <div class="faq">
    <details open><summary>Ürünlere isim ya da yazı eklenebilir mi?</summary><p>Ürüne uygunsa evet. Bir isim ya da kısa bir yazı ürüne işlenebilir. Hangi ürünlerde mümkün olduğunu WhatsApp'tan sorabilirsiniz.</p></details>
    <details><summary>Hangi renkler var?</summary><p>Renk seçenekleri atölyedeki güncel filament stoğuna göre değişir. Hangi renklerin hazır olduğunu sormanız yeterli.</p></details>
    <details><summary>Hangi malzemeleri kullanıyorsunuz?</summary><p>Malzeme ürünün kullanım amacına göre seçilir: PLA, PLA+, PETG, TPU ya da ABS ve benzeri teknik filamentler.</p></details>
    <details><summary>Aklımda sadece bir fikir var, 3D modelim yok. Olur mu?</summary><p>Olur. Fotoğraf, ölçü ya da sadece fikrinizi paylaşın. Uygun projelerde mevcut modelleri uyarlıyor, basit tasarımlar hazırlıyoruz.</p></details>
    <details><summary>Siparişim ne kadar sürede hazır olur?</summary><p>Üretim genellikle 2-3 iş günü sürer, çoğu zaman daha kısa. Toplu siparişlerde süre uzayabilir. Kargo süresi buna dahil değildir.</p></details>
    <details><summary>Kargo ücreti ne kadar?</summary><p>Shopier mağazamızdan verilen siparişlerde kargo ücreti 100 ₺'dir; 2.000 ₺ ve üzeri siparişlerde kargo ücretsizdir. Özel siparişlerde teslimat ayrıntılarını birlikte netleştiririz.</p></details>
    <details><summary>Toplu sipariş alıyor musunuz?</summary><p>Evet. Toplu alımlarda fiyat için WhatsApp ya da e-posta ile yazabilirsiniz.</p></details>
  </div>
</div></section>
</main>

<footer class="torn-t"><div class="wrap">
  <div class="foot-pics"><?php foreach(array_slice($pics,0,3) as $p): ?><a href="<?= h(urunUrl($p)) ?>"><img src="uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy"></a><?php endforeach; ?><a class="igt" href="<?= $ig ?>" rel="noopener"><?= ic('insta') ?><span><small>Bizi takip edin</small><b>@mikro3datolye</b></span></a></div>
  <div class="foot-cols">
    <div><span class="fbrand">Mikro 3D Atölye</span><p>Kişiye özel hediyelikler, özgün ev dekorasyonu ve kullanışlı tasarımlar üreten butik 3D baskı atölyesi.</p>
      <div class="social"><a href="<?= $ig ?>" rel="noopener" aria-label="Instagram"><?= ic('insta') ?></a><a href="<?= h($waMsg) ?>" rel="noopener" aria-label="WhatsApp"><?= ic('chat') ?></a><a href="mailto:<?= $mail ?>" aria-label="E-posta"><?= ic('mail') ?></a></div></div>
    <div><h3>Sayfalar</h3><ul><li><a href="#top">Ana sayfa</a></li><li><a href="#urunler">Ürünler</a></li><li><a href="#ozel">Özel sipariş</a></li><li><a href="#atolye">Atölye</a></li><li><a href="#sss">Sıkça sorulan sorular</a></li></ul></div>
    <div><h3>İletişim</h3><ul><li><a href="<?= h($waMsg) ?>" rel="noopener">WhatsApp: <?= $tel ?></a></li><li><a href="mailto:<?= $mail ?>"><?= $mail ?></a></li><li><a href="<?= $ig ?>" rel="noopener">Instagram: @mikro3datolye</a></li></ul></div>
    <div><h3>Mağazalar</h3><ul><li><a href="<?= $shopier ?>" rel="noopener">Shopier</a></li><li><a href="<?= $dolap ?>" rel="noopener">Dolap</a></li></ul></div>
  </div>
  <p class="wordmark" aria-hidden="true">MİKRO3D</p>
  <p class="copy"><span>© <?= date('Y') ?> Mikro 3D Atölye · Bursa</span><span><button type="button" class="linkbtn" id="consent-open">Çerez tercihi</button> · <a href="gizlilik.php">Gizlilik ve çerezler</a></span></p>
</div></footer>

<a class="wa-fab" href="<?= h($waMsg) ?>" rel="noopener" aria-label="WhatsApp'tan yaz"><?= ic('chat') ?></a>

<div class="consent" id="consent" role="region" aria-label="Çerez tercihi" hidden>
  <p>Bu site, ziyaretlerin nasıl kullanıldığını anlamak için Google Analytics çerezleri kullanır. Siz kabul etmeden analiz çerezleri çalışmaz. Tercihinizi sayfanın altındaki "Çerez tercihi" bağlantısından değiştirebilirsiniz. <a href="gizlilik.php">Ayrıntılı bilgi</a></p>
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
<script>
(function(){
  /* Instagram paylaşımları yalnızca düğmeye basılınca yüklenir */
  var KEY='m3d_ig', gate=document.getElementById('insta-gate'), grid=document.getElementById('insta-grid');
  function get(){ try{ return localStorage.getItem(KEY); }catch(e){ return null; } }
  function set(v){ try{ localStorage.setItem(KEY,v); }catch(e){} }
  function show(){
    gate.hidden=true; grid.hidden=false;
    if(window.instgrm){ window.instgrm.Embeds.process(); return; }
    var s=document.createElement('script'); s.async=true; s.src='https://www.instagram.com/embed.js';
    s.onload=function(){ if(window.instgrm) window.instgrm.Embeds.process(); };
    document.body.appendChild(s);
  }
  document.getElementById('insta-show').onclick=function(){ set('evet'); show(); };
  if(get()==='evet') show();

  /* Telefonda açılır menü */
  var hdr=document.querySelector('.top'), mb=hdr.querySelector('.menu-btn');
  function close(){ hdr.classList.remove('open'); mb.setAttribute('aria-expanded','false'); mb.setAttribute('aria-label','Menüyü aç'); }
  mb.onclick=function(){ var o=hdr.classList.toggle('open'); mb.setAttribute('aria-expanded',o?'true':'false'); mb.setAttribute('aria-label',o?'Menüyü kapat':'Menüyü aç'); };
  hdr.querySelectorAll('nav a').forEach(function(a){ a.addEventListener('click',close); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') close(); });

  /* İletişim formu: mesajı WhatsApp'ta hazır açar, hiçbir yere kaydetmez */
  var wf=document.getElementById('waform');
  wf.addEventListener('submit',function(e){
    e.preventDefault();
    var ad=wf.elements.namedItem('ad').value.trim(), m=wf.elements.namedItem('text').value.trim();
    var t='Merhaba'+(ad?', ben '+ad:'')+'. '+m;
    window.open(wf.action+'?text='+encodeURIComponent(t),'_blank','noopener');
  });

  /* Bölümler kaydırırken yumuşakça belirir */
  if(!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var els=document.querySelectorAll('.sec-head, .feat, .about-pic, .cat-tile, .shop .card, .why-grid article, .steps li, .strip, .gal .pola, .c-row, .form-panel, .faq details, .foot-pics a');
  var io=new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } }); },{rootMargin:'0px 0px -8% 0px'});
  els.forEach(function(el){
    var i=Array.prototype.indexOf.call(el.parentNode.children,el);
    if(!el.matches('.sec-head, .about-pic, .form-panel, .strip')) el.style.transitionDelay=Math.min(i,5)*70+'ms';
    el.classList.add('rv'); io.observe(el);
  });
})();
</script>
</body></html>
