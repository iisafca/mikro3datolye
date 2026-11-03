<?php require 'config.php';
$ig = 'https://ig.me/m/mikro3datolye';
$cats = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$c = $_GET['k'] ?? '';
$st = $c !== '' ? $db->prepare("SELECT * FROM products WHERE active=1 AND category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM products WHERE active=1 AND ?='' ORDER BY id DESC");
$st->execute([$c]); $items = $st->fetchAll();
function vase(){ $o=''; $n=30;
  for($i=0;$i<$n;$i++){ $t=$i/($n-1); $w=50+62*sin(M_PI*pow($t,.75))+10*pow($t,8);
    $o.=sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="8.6" rx="4.3" fill="hsl(%d 88%% 58%%)"/>',(240-$w)/2,285-$i*9.3,$w,205+$t*135); }
  return '<svg class="vase" viewBox="0 0 240 300" role="img" aria-label="Katman katman basılmış renkli bir vazo"><ellipse cx="120" cy="292" rx="80" ry="6" fill="#1b2236" opacity=".15"/>'.$o.'</svg>'; }
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mikro 3D Atölye | Kişiye özel 3D baskı hediyeler ve promosyon ürünleri</title>
<meta name="description" content="3D baskı ile ev dekorasyonu hediyeleri ve firmanıza özel promosyon ürünleri. İsim, logo ve renk seçimiyle kişiye özel.">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Figtree:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="site.css"></head><body>
<header class="hero"><div class="wrap"><div>
<h1>Fikrin senin, katmanları biz basarız.</h1>
<p>Ev dekorasyonu hediyeleri ve firmanıza özel promosyon ürünleri. İsim, logo, renk: hepsi kişiye özel.</p>
<div class="acts"><a class="btn" href="#urunler">Ürünlere bak</a><a class="btn line" href="<?= $ig ?>" rel="noopener">Kişiye özel iste</a></div></div>
<?= vase() ?></div></header>
<section class="two"><div class="a"><h2>Evinize ve sevdiklerinize</h2><p>Dekoratif objeler, isimlikler ve hediyelik küçük parçalar. Doğum günü, yeni ev ya da sadece içinizden geldiği için.</p><a class="btn" href="#urunler">Ev ürünlerini gör</a></div>
<div class="b"><h2>Firmanıza özel promosyon</h2><p>Logonuz ve kurumsal renkleriniz basılı ürünler. Müşterinize ya da ekibinize hatırlanacak bir hediye verin.</p><a class="btn" href="<?= $ig ?>" rel="noopener">Teklif iste</a></div></section>
<section class="steps"><div class="wrap"><h2>Kişiye özel sipariş nasıl işliyor?</h2><ol>
<li><b>Fikrini anlat</b><span>Instagram'dan yaz: ne istediğini, kaç adet olacağını ve renk tercihini söyle.</span></li>
<li><b>Tasarımı onayla</b><span>Taslağı ve fiyatı gönderirim. Onayladığında basıma başlarım.</span></li>
<li><b>Basılsın, kapına gelsin</b><span>Ürünün özenle paketlenip kargoya verilir.</span></li></ol></div></section>
<section class="shop" id="urunler"><div class="wrap"><h2>Hazır ürünler</h2>
<nav class="cats"><a href="./#urunler" class="<?= $c===''?'on':'' ?>">Tümü</a>
<?php foreach($cats as $k): ?><a href="?k=<?= urlencode($k) ?>#urunler" class="<?= $c===$k?'on':'' ?>"><?= h($k) ?></a><?php endforeach; ?></nav>
<?php if(!$items): ?><div class="empty"><b>Yeni ürünler yolda.</b><p>Şimdilik kişiye özel sipariş alıyoruz. Ne istediğini bize yaz, birlikte tasarlayalım.</p><a class="btn blue" href="<?= $ig ?>" rel="noopener">Instagram'dan yaz</a></div><?php endif; ?>
<div class="grid"><?php foreach($items as $p): ?><article class="card">
<?php if($p['image']): ?><img src="uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy"><?php else: ?><div class="ph"></div><?php endif; ?>
<div class="in"><small><?= h($p['category']) ?></small><h3><?= h($p['name']) ?></h3><p><?= h($p['description']) ?></p><span class="price"><?= tl($p['price']) ?></span>
<?php if($p['shopier_url']): ?><a class="btn blue" href="<?= h($p['shopier_url']) ?>" rel="noopener">Satın al</a><?php endif; ?>
<a class="more" href="<?= $ig ?>" rel="noopener">Kişiye özel yaptır</a></div></article><?php endforeach; ?></div></div></section>
<footer><div class="wrap"><h2>Aklındaki ürünü birlikte basalım</h2><a class="btn" href="<?= $ig ?>" rel="noopener">@mikro3datolye'ye yaz</a>
<p>© <?= date('Y') ?> Mikro 3D Atölye</p></div></footer></body></html>
