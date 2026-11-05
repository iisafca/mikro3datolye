<?php require 'config.php';
$wa     = 'https://wa.me/905346668102';
$waMsg  = $wa . '?text=' . rawurlencode('Merhaba, kişiye özel bir ürün hakkında bilgi almak istiyorum.');
$ig     = 'https://www.instagram.com/mikro3datolye';
$shopier= 'https://www.shopier.com/mikro3datolye';
$dolap  = 'https://link.dolap.com/1e0r7d';
$mail   = 'isafca16@gmail.com';
$cats = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$c = $_GET['k'] ?? '';
$st = $c !== '' ? $db->prepare("SELECT * FROM products WHERE active=1 AND category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM products WHERE active=1 AND ?='' ORDER BY id DESC");
$st->execute([$c]); $items = $st->fetchAll();
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
  <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-DM8M8T5C9S"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-DM8M8T5C9S');
</script>
<title>Mikro 3D Atölye | Kişiye özel 3D baskı hediyelik ve ev dekorasyonu</title>
<meta name="description" content="Kişiye özel hediyelikler, özgün ev dekorasyonu ve günlük hayatı kolaylaştıran tasarımlar üreten butik 3D baskı atölyesi. Fikrinizi birlikte ürüne dönüştürelim.">
<meta name="theme-color" content="#2a1f5c">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;700&family=Unbounded:wght@600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="site.css?v=3"></head><body>

<header class="top"><div class="wrap">
  <a class="logo" href="./" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"><i></i><i></i><i></i></span>mikro3d atölye</a>
  <nav aria-label="Ana menü"><a href="#urunler">Ürünler</a><a href="#ozel">Özel sipariş</a><a href="#atolye">Atölye</a><a class="btn sm" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></nav>
</div></header>

<section class="hero"><div class="wrap">
  <h1 class="print"><span class="txt">Bir fikirle başlar, 3D baskıyla gerçeğe dönüşür.</span><span class="nozzle" aria-hidden="true"></span></h1>
  <div class="hero-foot">
    <p>Mikro 3D Atölye; kişiye özel hediyelikler, özgün ev dekorasyonu ve günlük hayatı kolaylaştıran tasarımlar üreten butik bir 3D baskı atölyesi.</p>
    <div class="acts"><a class="btn" href="#urunler">Ürünlere bak</a><a class="btn ghost" href="<?= h($waMsg) ?>" rel="noopener">Özel sipariş için yaz</a></div>
  </div>
</div></section>

<section class="lanes">
  <div class="lane home"><div class="in">
    <h2>Sevdiklerinize özel</h2>
    <p>İsimli objeler, masa süsleri ve özel günler için hediyelikler. Ürüne uygunsa isim, renk ve ölçüyü siz seçersiniz.</p>
    <a class="btn light" href="#urunler">Hediyelikleri gör</a>
  </div></div>
  <div class="lane biz"><div class="in">
    <h2>Yaşam alanınıza farklı</h2>
    <p>Vazolar, figürler, geometrik lamba gövdeleri ve raf süsleri. Evinize ya da masanıza özgün bir parça ekleyin.</p>
    <a class="btn" href="#urunler">Dekorasyonu gör</a>
  </div></div>
</section>

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

<section class="shop" id="urunler"><div class="wrap">
  <div class="shop-head"><h2>Ürünler</h2>
  <?php if($cats): ?><nav class="cats" aria-label="Kategoriler"><a href="./#urunler"<?= $c===''?' aria-current="page"':'' ?>>Tümü</a><?php foreach($cats as $k): ?><a href="?k=<?= urlencode($k) ?>#urunler"<?= $c===$k?' aria-current="page"':'' ?>><?= h($k) ?></a><?php endforeach; ?></nav><?php endif; ?>
  </div>
  <p class="stores">Mevcut ürünler için mağazalarımıza da bakabilirsiniz: <a href="<?= $shopier ?>" rel="noopener">Shopier</a> ve <a href="<?= $dolap ?>" rel="noopener">Dolap</a>.</p>
  <?php if(!$items): ?><div class="empty"><b>Ürünler yakında burada.</b><p>Şimdilik Shopier ve Dolap mağazalarımıza göz atabilir ya da aklınızdaki ürünü bize yazabilirsiniz.</p>
    <div class="acts"><a class="btn" href="<?= $shopier ?>" rel="noopener">Shopier mağazası</a><a class="btn ghost" href="<?= $dolap ?>" rel="noopener">Dolap mağazası</a><a class="btn ghost" href="<?= h($waMsg) ?>" rel="noopener">WhatsApp'tan yaz</a></div></div><?php endif; ?>
  <div class="grid"><?php foreach($items as $p): $pm = $wa.'?text='.rawurlencode('Merhaba, "'.$p['name'].'" ürünü hakkında bilgi almak istiyorum.'); ?><article class="card">
    <div class="pic"><?php if($p['image']): ?><img src="uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy"><?php else: ?><span class="ph" aria-hidden="true"></span><?php endif; ?></div>
    <div class="in"><span class="cat"><?= h($p['category']) ?></span><h3><?= h($p['name']) ?></h3>
    <?php if($p['description']): ?><p><?= h($p['description']) ?></p><?php endif; ?>
    <div class="buy"><span class="price"><?= tl($p['price']) ?></span>
    <?php if($p['shopier_url']): ?><a class="btn sm" href="<?= h($p['shopier_url']) ?>" rel="noopener">Satın al</a><?php endif; ?></div>
    <a class="more" href="<?= h($pm) ?>" rel="noopener">Renk ve kişiselleştirme için yazın</a></div>
  </article><?php endforeach; ?></div>
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
  <p class="copy">© <?= date('Y') ?> Mikro 3D Atölye</p>
</div></footer>
</body></html>
