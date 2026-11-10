<?php require 'config.php'; require 'urun-ortak.php';
/* Site haritası: ana sayfa + yayındaki her ürün. .htaccess, /sitemap.xml adresini bu dosyaya yönlendirir;
   yeni ürün eklediğinizde Google'a ayrıca bir şey bildirmeniz gerekmez. */
header('Content-Type: application/xml; charset=utf-8');
$urls=[SITE.'/'];
foreach($db->query("SELECT id,name FROM products WHERE active=1 ORDER BY id DESC") as $p) $urls[]=SITE.urunUrl($p);
echo '<?xml version="1.0" encoding="UTF-8"?>',"\n",'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',"\n";
foreach($urls as $u) echo '  <url><loc>',htmlspecialchars($u,ENT_XML1|ENT_QUOTES,'UTF-8'),'</loc></url>',"\n";
echo '</urlset>',"\n";
