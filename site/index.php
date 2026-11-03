<?php require 'config.php';
$ig = 'https://ig.me/m/mikro3datolye';
$cats = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$c = $_GET['k'] ?? '';
$st = $c !== '' ? $db->prepare("SELECT * FROM products WHERE active=1 AND category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM products WHERE active=1 AND ?='' ORDER BY id DESC");
$st->execute([$c]); $items = $st->fetchAll();
$spools = [['Mercan','#ff4f7b'],['Lime','#b8e43a'],['Camgöbeği','#29c4e0'],['Güneş','#ffc531'],['Mürekkep','#2a1f5c'],['Beyaz','#ffffff']];
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mikro 3D Atölye | Kişiye özel 3D baskı hediyeler ve promosyon ürünleri</title>
<meta name="description" content="3D baskı ile ev dekorasyonu hediyeleri ve firmanıza özel promosyon ürünleri. İsim, logo ve renk seçimiyle kişiye özel.">
<meta name="theme-color" content="#2a1f5c">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;700&family=Unbounded:wght@600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="site.css?v=2"></head><body>

<header class="top"><div class="wrap">
  <a class="logo" href="./" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"><i></i><i></i><i></i></span>mikro3d atölye</a>
  <nav aria-label="Ana menü"><a href="#urunler">Ürünler</a><a href="#siparis">Sipariş nasıl verilir</a><a class="btn sm" href="<?= $ig ?>" rel="noopener">Instagram'dan yaz</a></nav>
</div></header>

<section class="hero"><div class="wrap">
  <h1 class="print"><span class="txt">Fikrin senin, katmanları biz basarız.</span><span class="nozzle" aria-hidden="true"></span></h1>
  <div class="hero-foot">
    <p>Ev dekorasyonu hediyeleri ve firmanıza özel promosyon ürünleri. İsim, logo ve renk seçimi tamamen size ait.</p>
    <div class="acts"><a class="btn" href="#urunler">Ürünlere bak</a><a class="btn ghost" href="<?= $ig ?>" rel="noopener">Kişiye özel iste</a></div>
  </div>
</div></section>

<section class="lanes">
  <div class="lane home"><div class="in">
    <h2>Evinize ve sevdiklerinize</h2>
    <p>Dekoratif objeler, isimlikler ve hediyelik küçük parçalar. Doğum günü, yeni ev ya da sadece içinizden geldiği için.</p>
    <a class="btn light" href="#urunler">Ev ürünlerini gör</a>
  </div></div>
  <div class="lane biz"><div class="in">
    <h2>Firmanıza özel promosyon</h2>
    <p>Logonuz ve kurumsal renkleriniz basılı ürünler. Müşterinize ya da ekibinize hatırlanacak bir hediye verin.</p>
    <a class="btn" href="<?= $ig ?>" rel="noopener">Teklif iste</a>
  </div></div>
</section>

<section class="custom"><div class="wrap">
  <div class="custom-txt">
    <h2>Üç şeyi siz seçersiniz</h2>
    <dl>
      <div><dt>İsim ya da yazı</dt><dd>Bir isim, tarih ya da kısa bir not ürünün üzerine basılır.</dd></div>
      <div><dt>Logo</dt><dd>Firmanızın logosunu gönderin, ürüne uygun hale getirelim.</dd></div>
      <div><dt>Renk</dt><dd>Filament rengini siz seçin; tek renk ya da birkaç renk bir arada.</dd></div>
    </dl>
  </div>
  <ul class="spools" aria-label="Örnek filament renkleri">
    <?php foreach($spools as [$n,$hex]): ?><li style="--c:<?= $hex ?>"><span class="spool" aria-hidden="true"></span><?= h($n) ?></li><?php endforeach; ?>
  </ul>
</div></section>

<section class="steps" id="siparis"><div class="wrap">
  <h2>Kişiye özel sipariş nasıl işliyor?</h2>
  <ol>
    <li><span class="n" aria-hidden="true">1</span><b>Fikrinizi anlatın</b><p>Instagram'dan yazın: ne istediğinizi, kaç adet olacağını ve renk tercihinizi söyleyin.</p></li>
    <li><span class="n" aria-hidden="true">2</span><b>Taslağı onaylayın</b><p>Taslağı ve fiyatı gönderiyoruz. Onayladığınızda baskıya başlıyoruz.</p></li>
    <li><span class="n" aria-hidden="true">3</span><b>Basılsın, kapınıza gelsin</b><p>Ürününüz özenle paketlenip kargoya verilir.</p></li>
  </ol>
</div></section>

<section class="shop" id="urunler"><div class="wrap">
  <div class="shop-head"><h2>Hazır ürünler</h2>
  <?php if($cats): ?><nav class="cats" aria-label="Kategoriler"><a href="./#urunler"<?= $c===''?' aria-current="page"':'' ?>>Tümü</a><?php foreach($cats as $k): ?><a href="?k=<?= urlencode($k) ?>#urunler"<?= $c===$k?' aria-current="page"':'' ?>><?= h($k) ?></a><?php endforeach; ?></nav><?php endif; ?>
  </div>
  <?php if(!$items): ?><div class="empty"><b>Yeni ürünler yolda.</b><p>Şimdilik kişiye özel sipariş alıyoruz. Ne istediğinizi yazın, birlikte tasarlayalım.</p><a class="btn" href="<?= $ig ?>" rel="noopener">Instagram'dan yaz</a></div><?php endif; ?>
  <div class="grid"><?php foreach($items as $p): ?><article class="card">
    <div class="pic"><?php if($p['image']): ?><img src="uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy"><?php else: ?><span class="ph" aria-hidden="true"></span><?php endif; ?></div>
    <div class="in"><span class="cat"><?= h($p['category']) ?></span><h3><?= h($p['name']) ?></h3>
    <?php if($p['description']): ?><p><?= h($p['description']) ?></p><?php endif; ?>
    <div class="buy"><span class="price"><?= tl($p['price']) ?></span>
    <?php if($p['shopier_url']): ?><a class="btn sm" href="<?= h($p['shopier_url']) ?>" rel="noopener">Satın al</a><?php endif; ?></div>
    <a class="more" href="<?= $ig ?>" rel="noopener">Kişiye özel yaptır</a></div>
  </article><?php endforeach; ?></div>
</div></section>

<footer><div class="wrap">
  <h2>Aklınızdaki ürünü birlikte basalım.</h2>
  <a class="btn light" href="<?= $ig ?>" rel="noopener">@mikro3datolye'ye yaz</a>
  <p class="copy">© <?= date('Y') ?> Mikro 3D Atölye</p>
</div></footer>
</body></html>
