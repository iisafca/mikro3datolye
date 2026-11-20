<?php
/* Rehber yazıları için ortak parçalar: adres, kısa yazım biçimini HTML'e çevirme, yazı kartı.
   rehber.php, sitemap.php, index.php, urun.php ve panel-yazi.php kullanır. config.php ve urun-ortak.php'den sonra çağrılmalıdır. */

// Yazının kalıcı adresi: /rehber/ledli-isimlik-nedir
function yaziUrl($y){ return '/rehber/'.$y['slug']; }

// Yayındaki yazı sayısı (menüde "Rehber" bağlantısı yalnızca yazı varsa görünür). Tablo henüz yoksa 0 döner.
function yaziSay($db){
  try{ return (int)$db->query("SELECT COUNT(*) FROM posts WHERE published=1")->fetchColumn(); }catch(Throwable $e){ return 0; }
}

/* Paneldeki yazı metnini güvenli HTML'e çevirir. Desteklenen yazım:
     ## Ara başlık          ### Küçük ara başlık
     - madde                1. numaralı madde
     **kalın**  *italik*    [bağlantı metni](/urun/5-ledli-isimlik)  ya da  [metin](https://...)
   Boş satır yeni paragraf başlatır. Metindeki HTML etiketleri çalışmaz, düz yazı olarak görünür. */
function yaziSatir($s){
  $s=h($s);
  $s=preg_replace('/\*\*(.+?)\*\*/u','<strong>$1</strong>',$s);
  $s=preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u','<em>$1</em>',$s);
  return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u',function($m){
    $u=html_entity_decode($m[2],ENT_QUOTES,'UTF-8');
    if(!preg_match('#^(https?://|/(?!/)|\#)#i',$u)) return $m[0];   // yalnızca site içi ya da http(s) bağlantılar
    $dis=preg_match('#^https?://#i',$u) && stripos($u,SITE)!==0;
    return '<a href="'.h($u).'"'.($dis?' rel="noopener"':'').'>'.$m[1].'</a>';
  },$s);
}
function yaziHtml($metin){
  $out=''; $par=[]; $list=null; $items=[];
  $flushP=function() use(&$out,&$par){ if($par){ $out.='<p>'.implode(' ',array_map('yaziSatir',$par)).'</p>'; $par=[]; } };
  $flushL=function() use(&$out,&$list,&$items){ if($list){ $out.="<$list>".implode('',array_map(fn($i)=>'<li>'.yaziSatir($i).'</li>',$items))."</$list>"; $list=null; $items=[]; } };
  foreach(preg_split('/\R/u',(string)$metin) as $ln){
    $t=trim($ln);
    if($t===''){ $flushP(); $flushL(); continue; }
    if(preg_match('/^(#{1,3})\s+(.+)$/u',$t,$m)){ $flushP(); $flushL(); $lv=strlen($m[1])===3?3:2; $out.="<h$lv>".yaziSatir($m[2])."</h$lv>"; continue; }
    if(preg_match('/^[-*•]\s+(.+)$/u',$t,$m)){ $flushP(); if($list!=='ul'){ $flushL(); $list='ul'; } $items[]=$m[1]; continue; }
    if(preg_match('/^\d+[.)]\s+(.+)$/u',$t,$m)){ $flushP(); if($list!=='ol'){ $flushL(); $list='ol'; } $items[]=$m[1]; continue; }
    $flushL(); $par[]=$t;
  }
  $flushP(); $flushL();
  return $out;
}

// Okuma süresi (dakika), dakikada ~200 kelime
function yaziSure($metin){ return max(1,(int)round(count(preg_split('/\s+/u',trim(strip_tags((string)$metin)),-1,PREG_SPLIT_NO_EMPTY))/200)); }

function tarihTr($d){
  static $A=['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
  $t=strtotime((string)$d); return $t ? date('j',$t).' '.$A[(int)date('n',$t)].' '.date('Y',$t) : '';
}

// Rehber listesinde ve ürün sayfalarında kullanılan kart
function yaziKart($y){
  $u=h(yaziUrl($y)); $n=h($y['title']);
  $pic=$y['image'] ? '<img src="/uploads/'.h($y['image']).'" alt="" loading="lazy">' : '<span class="ph" aria-hidden="true"></span>';
  return '<article class="card post-card">'
    .'<a class="pic" href="'.$u.'" tabindex="-1" aria-hidden="true">'.$pic.'</a>'
    .'<div class="in"><span class="cat">Rehber · '.yaziSure($y['body']??'').' dk</span><h3><a href="'.$u.'">'.$n.'</a></h3>'
    .($y['description'] ? '<p>'.h(kisalt($y['description'],140)).'</p>' : '')
    .'<a class="more" href="'.$u.'">Yazıyı oku</a></div></article>';
}
