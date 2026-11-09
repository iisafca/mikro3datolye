<?php
/* Atölye uygulamasının simgesi: yeşil zemin üzerinde katmanlı bir küp (PNG, GD ile çizilir).
   Kullanım: app-icon.php?s=180 | 192 | 512 */
$s=(int)($_GET['s']??192); if(!in_array($s,[180,192,512],true)) $s=192;
if(!function_exists('imagecreatetruecolor')){ http_response_code(404); exit; }

$K=4; $S=$s*$K; $im=imagecreatetruecolor($S,$S);   // 4 kat büyük çizip küçültünce kenarlar yumuşar
function rgb($im,$hex){ return imagecolorallocate($im,hexdec(substr($hex,1,2)),hexdec(substr($hex,3,2)),hexdec(substr($hex,5,2))); }
function poly($im,$pts,$c){ $a=[]; foreach($pts as $p){ $a[]=(int)round($p[0]); $a[]=(int)round($p[1]); } imagefilledpolygon($im,$a,$c); }
function line($im,$p,$q,$c){ imageline($im,(int)round($p[0]),(int)round($p[1]),(int)round($q[0]),(int)round($q[1]),$c); }
function dn($p,$d){ return [$p[0],$p[1]+$d]; }

$bg=rgb($im,'#0b7a75'); imagefilledrectangle($im,0,0,$S,$S,$bg);

// Küp, simgenin ortasındaki güvenli alanda kalır (yuvarlak/maskeli simgelerde de kesilmez)
$cx=$S/2; $cy=$S/2; $r=$S*0.29; $w=$r*cos(deg2rad(30));
$top=[$cx,$cy-$r]; $ur=[$cx+$w,$cy-$r/2]; $lr=[$cx+$w,$cy+$r/2]; $bot=[$cx,$cy+$r]; $ll=[$cx-$w,$cy+$r/2]; $ul=[$cx-$w,$cy-$r/2]; $c=[$cx,$cy];

poly($im,[$top,$ur,$c,$ul],rgb($im,'#ffffff'));
poly($im,[$ul,$c,$bot,$ll],rgb($im,'#d3ece9'));
poly($im,[$c,$ur,$lr,$bot],rgb($im,'#97cfc9'));

// Yan yüzlerde baskı katmanlarını andıran çizgiler
imagesetthickness($im,max(1,(int)round($S*0.009)));
$l1=rgb($im,'#b5dbd7'); $l2=rgb($im,'#7dbcb5');
for($i=1;$i<=4;$i++){ $d=$r*$i/5; line($im,dn($ul,$d),dn($c,$d),$l1); line($im,dn($c,$d),dn($ur,$d),$l2); }

// Yüzleri ayıran ince çizgiler
imagesetthickness($im,max(1,(int)round($S*0.014)));
line($im,$c,$ul,$bg); line($im,$c,$ur,$bg); line($im,$c,$bot,$bg);

$out=imagecreatetruecolor($s,$s); imagecopyresampled($out,$im,0,0,0,0,$s,$s,$S,$S);
header('Content-Type: image/png'); header('Cache-Control: public, max-age=604800');
imagepng($out);
