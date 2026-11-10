<?php require 'config.php'; require 'urun-ortak.php';
$wa     = WA;
$waMsg  = $wa . '?text=' . rawurlencode('Merhaba, kişiye özel bir ürün hakkında bilgi almak istiyorum.');
$ig     = 'https://www.instagram.com/mikro3datolye';
$shopier= 'https://www.shopier.com/mikro3datolye';
$dolap  = 'https://link.dolap.com/1e0r7d';
$mail   = 'isafca16@gmail.com';
$cats = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$c = $_GET['k'] ?? '';
$st = $c !== '' ? $db->prepare("SELECT * FROM products WHERE active=1 AND category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM products WHERE active=1 AND ?='' ORDER BY id DESC");
$st->execute([$c]); $items = $st->fetchAll();
// Giriş bölümünde gösterilen ürün: fotoğrafı olan en yeni ürün
$feat = $db->query("SELECT * FROM products WHERE active=1 AND image IS NOT NULL AND image<>'' ORDER BY id DESC LIMIT 1")->fetch();
$spools = ['#ff4f7b','#b8e43a','#29c4e0','#ffc531','#2a1f5c','#ffffff'];
$areas = [
  ['Kişiye özel hediyelikler','İsimli objeler, masa süsleri, hatıra ürünleri ve küçük hediye şişeleri için isimlikler.','--c:var(--coral)'],
  ['Bebek ve çocuk','Aylık bebek fotoğraf çekimi aksesuarları, isimli oda süsleri ve çocuklar için sevimli figürler.','--c:var(--sun)'],
  ['Ev dekorasyonu','Vazolar, figürler, masa ve raf süsleri, özgün geometrik tasarımlar.','--c:var(--cyan)'],
  ['Aydınlatma','Masa lambaları ve ortam aydınlatmaları için geometrik desenli, minimal lamba gövdeleri.','--c:var(--lime)'],
  ['Kullanışlı ürünler','Masaüstü düzenleyiciler, kalemlikler, kablo düzenleyiciler, özel ölçülü aparat ve tutucular.','--c:var(--coral)'],
  ['Özel tasarım','Fotoğraf, ölçü ya da sadece bir fikir paylaşın. Uygun projelerde mevcut modelleri uyarlıyor, basit tasarımlar hazırlıyoruz.','--c:var(--cyan)'],
];
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
<title>Mikro 3D Atölye | Kişiye özel 3D baskı hediyelik ve ev dekorasyonu</title>
<meta name="description" content="Kişiye özel hediyelikler, özgün ev dekorasyonu ve günlük hayatı kolaylaştıran tasarımlar üreten butik 3D baskı atölyesi. Fikrinizi birlikte ürüne dönüştürelim.">
<meta name="theme-color" content="#1d1b33">
<link rel="preload" href="unbounded-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="site.css?v=6"></head><body>

<header class="top"><div class="wrap">
  <a class="logo" href="./" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"><i></i><i></i><i></i></span>mikro3d atölye</a>
  <nav aria-label="Ana menü"><a href="#urunler">Ürünler</a><a href="#ozel">Özel sipariş</a><a href="#atolye">Atölye</a><a class="btn sm" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></nav>
</div></header>

<section class="hero<?= $feat ? '' : ' solo' ?>"><div class="wrap">
  <div class="hero-txt">
    <p class="eyebrow">Kişiye özel 3D baskı</p>
    <h1>Bir fikirle başlar, 3D baskıyla gerçeğe dönüşür.</h1>
    <p>Mikro 3D Atölye; kişiye özel hediyelikler, özgün ev dekorasyonu ve günlük hayatı kolaylaştıran tasarımlar üreten butik bir 3D baskı atölyesi.</p>
    <div class="acts"><a class="btn" href="#urunler">Ürünlere bak</a><a class="btn ghost" href="<?= h($waMsg) ?>" rel="noopener">Özel sipariş için yaz</a></div>
  </div>
  <?php if($feat): ?><a class="hero-pic" href="<?= h(urunUrl($feat)) ?>"><img src="uploads/<?= h($feat['image']) ?>" alt="<?= h($feat['name']) ?>"><span class="tag"><?= h($feat['name']) ?> <small><?= tl($feat['price']) ?></small></span></a><?php endif; ?>
</div></section>

<section class="shop" id="urunler"><div class="wrap">
  <div class="shop-head"><h2>Ürünler</h2>
  <?php if($cats): ?><nav class="cats" aria-label="Kategoriler"><a href="./#urunler"<?= $c===''?' aria-current="page"':'' ?>>Tümü</a><?php foreach($cats as $k): ?><a href="?k=<?= urlencode($k) ?>#urunler"<?= $c===$k?' aria-current="page"':'' ?>><?= h($k) ?></a><?php endforeach; ?></nav><?php endif; ?>
  </div>
  <p class="stores">Mevcut ürünler için mağazalarımıza da bakabilirsiniz: <a href="<?= $shopier ?>" rel="noopener">Shopier</a> ve <a href="<?= $dolap ?>" rel="noopener">Dolap</a>.</p>
  <?php if(!$items): ?><div class="empty"><b>Ürünler yakında burada.</b><p>Şimdilik Shopier ve Dolap mağazalarımıza göz atabilir ya da aklınızdaki ürünü bize yazabilirsiniz.</p>
    <div class="acts"><a class="btn" href="<?= $shopier ?>" rel="noopener">Shopier mağazası</a><a class="btn ghost" href="<?= $dolap ?>" rel="noopener">Dolap mağazası</a><a class="btn ghost" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></div></div><?php endif; ?>
  <div class="grid"><?php foreach($items as $p) echo urunKart($p); ?></div>
</div></section>

<section class="areas"><div class="wrap">
  <h2>Neler üretiyoruz?</h2>
  <div class="area-grid">
    <?php foreach($areas as [$t,$d,$s]): ?><article style="<?= $s ?>"><h3><?= h($t) ?></h3><p><?= h($d) ?></p></article><?php endforeach; ?>
  </div>
</div></section>

<section class="custom" id="ozel"><div class="wrap">
  <div class="custom-txt">
    <h2>İsim, renk ve ölçü sizden</h2>
    <p class="lead">Kişiselleştirme ürüne göre değişir. Fikrinizin 3D baskıya uygun olup olmadığına birlikte bakarız.</p>
    <dl>
      <div><dt>İsim ya da yazı</dt><dd>Bir isim ya da kısa bir yazı ürüne işlenebilir.</dd></div>
      <div><dt>Renk</dt><dd>Renk seçenekleri atölyedeki güncel filament stoğuna göre değişir. Hangi renklerin hazır olduğunu sorabilirsiniz.</dd></div>
      <div><dt>Ölçü</dt><dd>Fotoğraf ya da ölçü paylaşın; ürünü ihtiyacınıza göre uyarlayalım.</dd></div>
    </dl>
  </div>
  <ul class="spools" aria-hidden="true">
    <?php foreach($spools as $hex): ?><li style="--c:<?= $hex ?>"><span class="spool"></span></li><?php endforeach; ?>
  </ul>
</div></section>

<section class="steps"><div class="wrap">
  <h2>Özel sipariş nasıl işliyor?</h2>
  <ol>
    <li><span class="n" aria-hidden="true">1</span><b>Fikrinizi anlatın</b><p>WhatsApp'tan fotoğraf, ölçü ya da sadece fikrinizi yazın. Hazır 3D modeliniz olması gerekmiyor.</p></li>
    <li><span class="n" aria-hidden="true">2</span><b>Birlikte netleştirelim</b><p>Ürünün uygunluğuna, malzemeye ve rengine birlikte karar veririz. Fiyat ve ayrıntıları da burada konuşuruz.</p></li>
    <li><span class="n" aria-hidden="true">3</span><b>Atölyede basılsın</b><p>Onayınızdan sonra ürün atölyede basılır. Teslimat ayrıntılarını birlikte netleştiririz.</p></li>
  </ol>
</div></section>

<section class="insta" id="instagram"><div class="wrap">
  <div class="insta-head"><h2>Atölyeden</h2><a class="btn sm ghost" href="<?= $ig ?>" rel="noopener">Instagram'da takip edin</a></div>
  <p class="lead">Son çalışmalarımızı Instagram'da paylaşıyoruz.</p>
  <div class="insta-gate" id="insta-gate">
    <p>Paylaşımları göstermek için Instagram'ın kendi kodu yüklenir ve Instagram çerez kullanabilir. Düğmeye basana kadar hiçbir şey yüklenmez.</p>
    <button type="button" class="btn sm" id="insta-show">Paylaşımları göster</button>
  </div>
  <div class="insta-grid" id="insta-grid" hidden>
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/DfUW1MQEdQu/" data-instgrm-version="14"><a href="https://www.instagram.com/p/DfUW1MQEdQu/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/DfNewD3EVX3/" data-instgrm-version="14"><a href="https://www.instagram.com/p/DfNewD3EVX3/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/DfCdVeEDcUP/" data-instgrm-version="14"><a href="https://www.instagram.com/p/DfCdVeEDcUP/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/De7Tb1gEcvv/" data-instgrm-version="14"><a href="https://www.instagram.com/p/De7Tb1gEcvv/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/DepeuPOEb8x/" data-instgrm-version="14"><a href="https://www.instagram.com/p/DepeuPOEb8x/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
    <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/DehiU-LERYV/" data-instgrm-version="14"><a href="https://www.instagram.com/p/DehiU-LERYV/" rel="noopener">Paylaşımı Instagram'da gör</a></blockquote>
  </div>
</div></section>

<section class="about" id="atolye"><div class="wrap">
  <h2>Küçük bir atölye, kişisel bir üretim</h2>
  <div class="about-cols">
    <p>Mikro 3D Atölye butik bir atölye. Ürünleri FDM yöntemiyle, Creality Ender 3 V3 CoreXZ yazıcıda basıyoruz. Hazır modelleri ihtiyacınıza göre uyarlıyor, gerektiğinde yeni tasarımlar hazırlıyoruz.</p>
    <div><p>Malzeme, ürünün kullanım amacına göre seçilir.</p>
    <ul class="chips" aria-label="Kullanılabilen malzemeler"><li>PLA</li><li>PLA+</li><li>PETG</li><li>TPU</li><li>ABS ve benzeri teknik filamentler</li></ul></div>
  </div>
</div></section>

<footer><div class="wrap">
  <h2>Aklınızdaki ürünü birlikte tasarlayalım.</h2>
  <a class="btn light" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yazın</a>
  <ul class="links" aria-label="İletişim ve mağazalar">
    <li><a href="<?= $ig ?>" rel="noopener">Instagram</a></li>
    <li><a href="<?= $shopier ?>" rel="noopener">Shopier</a></li>
    <li><a href="<?= $dolap ?>" rel="noopener">Dolap</a></li>
    <li><a href="mailto:<?= $mail ?>"><?= $mail ?></a></li>
  </ul>
  <p class="copy">© <?= date('Y') ?> Mikro 3D Atölye · <button type="button" class="linkbtn" id="consent-open">Çerez tercihi</button> · <a href="gizlilik.php">Gizlilik ve çerezler</a></p>
</div></footer>

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
})();
</script>
<script>
/* Bölümler kaydırırken yumuşakça belirir. Hareket azaltma tercihi açıksa ya da tarayıcı desteklemiyorsa her şey doğrudan görünür. */
(function(){
  if(!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var els=document.querySelectorAll('section:not(.hero) h2, .stores, .card, .empty, .area-grid article, .custom .lead, .custom dl div, .spools, .steps li, .insta .lead, .insta-gate, .about-cols > *');
  var io=new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } }); },{rootMargin:'0px 0px -8% 0px'});
  els.forEach(function(el){
    var i=Array.prototype.indexOf.call(el.parentNode.children,el);
    if(el.matches('.card, .area-grid article, .steps li, .custom dl div')) el.style.transitionDelay=Math.min(i,5)*70+'ms';
    el.classList.add('rv'); io.observe(el);
  });
})();
</script>
</body></html>
