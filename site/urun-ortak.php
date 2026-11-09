<?php
/* Ürün sayfaları için ortak parçalar: adres (slug), kısa açıklama ve ürün kartı.
   index.php, urun.php, sitemap.php ve admin.php kullanır. config.php'den sonra çağrılmalıdır. */
const SITE='https://mikro3datolye.com.tr';
const WA='https://wa.me/905346668102';

// "İsimli Anahtarlık – Elif" -> "isimli-anahtarlik-elif"
function slug($s){
  $s=strtr((string)$s,['ç'=>'c','Ç'=>'c','ğ'=>'g','Ğ'=>'g','ı'=>'i','I'=>'i','İ'=>'i','ö'=>'o','Ö'=>'o','ş'=>'s','Ş'=>'s','ü'=>'u','Ü'=>'u','â'=>'a','î'=>'i','û'=>'u']);
  $s=preg_replace('/[^a-z0-9]+/','-',strtolower($s));
  return trim(substr($s,0,60),'-') ?: 'urun';
}
// Ürünün kalıcı adresi: /urun/12-isimli-anahtarlik
function urunUrl($p){ return '/urun/'.(int)$p['id'].'-'.slug($p['name']); }
// Metni kelime ortasından bölmeden kısaltır
function kisalt($s,$n){
  $s=trim(preg_replace('/\s+/u',' ',(string)$s));
  if(preg_match('/^.{0,'.$n.'}$/us',$s)) return $s;
  preg_match('/^.{0,'.$n.'}/us',$s,$m); return rtrim(preg_replace('/\s+\S*$/u','',$m[0]),' ,.;:-–').'…';
}
function urunMesaj($p){ return WA.'?text='.rawurlencode('Merhaba, "'.$p['name'].'" ürünü hakkında bilgi almak istiyorum.'); }

// Ana sayfada ve "Benzer ürünler"de kullanılan kart
function urunKart($p){
  $u=h(urunUrl($p)); $n=h($p['name']);
  $pic=$p['image'] ? '<img src="/uploads/'.h($p['image']).'" alt="'.$n.'" loading="lazy">' : '<span class="ph" aria-hidden="true"></span>';
  return '<article class="card">'
    .'<a class="pic" href="'.$u.'" tabindex="-1" aria-hidden="true">'.$pic.'</a>'
    .'<div class="in"><span class="cat">'.h($p['category']).'</span><h3><a href="'.$u.'">'.$n.'</a></h3>'
    .($p['description'] ? '<p>'.h(kisalt($p['description'],130)).'</p>' : '')
    .'<div class="buy"><span class="price">'.tl($p['price']).'</span>'
    .($p['shopier_url'] ? '<a class="btn sm" href="'.h($p['shopier_url']).'" rel="noopener">Satın al</a>' : '<a class="btn sm ghost" href="'.$u.'">İncele</a>')
    .'</div><a class="more" href="'.h(urunMesaj($p)).'" rel="noopener">Renk ve kişiselleştirme için yazın</a></div></article>';
}
