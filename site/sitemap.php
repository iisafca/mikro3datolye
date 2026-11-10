<?php require 'config.php'; require 'urun-ortak.php';
/* Site haritası: ana sayfa + yayındaki her ürün (fotoğrafıyla) + gizlilik sayfası. .htaccess, /sitemap.xml adresini
   bu dosyaya yönlendirir; yeni ürün eklediğinizde Google'a ayrıca bir şey bildirmeniz gerekmez. */
header('Content-Type: application/xml; charset=utf-8');
function x($s){ return htmlspecialchars((string)$s,ENT_XML1|ENT_QUOTES,'UTF-8'); }
echo '<?xml version="1.0" encoding="UTF-8"?>',"\n",'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',"\n";
echo '  <url><loc>',x(SITE.'/'),'</loc></url>',"\n";
foreach($db->query("SELECT id,name,image FROM products WHERE active=1 ORDER BY id DESC") as $p){
  echo '  <url><loc>',x(SITE.urunUrl($p)),'</loc>';
  if(!empty($p['image'])) echo '<image:image><image:loc>',x(SITE.'/uploads/'.$p['image']),'</image:loc></image:image>';
  echo '</url>',"\n";
}
echo '  <url><loc>',x(SITE.'/gizlilik.php'),'</loc></url>',"\n";
echo '</urlset>',"\n";
