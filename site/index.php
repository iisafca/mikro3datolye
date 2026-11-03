<?php require 'config.php';
$cats = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$c = $_GET['k'] ?? '';
$st = $c !== '' ? $db->prepare("SELECT * FROM products WHERE active=1 AND category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM products WHERE active=1 AND ?='' ORDER BY id DESC");
$st->execute([$c]); $items = $st->fetchAll();
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mikro 3D Atölye</title><link rel="stylesheet" href="style.css"></head><body>
<header class="top"><div class="wrap"><h1>Mikro 3D Atölye</h1><p>Katman katman, elde tasarlanıp basılan ürünler.</p></div></header>
<main class="wrap"><nav class="cats"><a href="./" class="<?= $c===''?'on':'' ?>">Tümü</a>
<?php foreach($cats as $k): ?><a href="?k=<?= urlencode($k) ?>" class="<?= $c===$k?'on':'' ?>"><?= h($k) ?></a><?php endforeach; ?></nav>
<?php if(!$items): ?><p class="empty">Henüz ürün yok. Yeni ürünler için Instagram'dan takipte kalın.</p><?php endif; ?>
<section class="grid"><?php foreach($items as $p): ?><article class="card">
<?php if($p['image']): ?><img src="uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy"><?php endif; ?>
<div><h3><?= h($p['name']) ?></h3><small><?= h($p['category']) ?></small><p style="margin:0;color:var(--mut)"><?= h($p['description']) ?></p>
<span class="price"><?= tl($p['price']) ?></span>
<?php if($p['shopier_url']): ?><a class="btn" href="<?= h($p['shopier_url']) ?>" rel="noopener">Shopier'de satın al</a><?php endif; ?></div></article><?php endforeach; ?></section></main>
<footer class="wrap"><a href="https://www.instagram.com/mikro3datolye/" rel="noopener">Instagram: @mikro3datolye</a></footer></body></html>
