<?php
/* Atölye simgesi: koyu zemin, yazıcı ucu (nozul) ve altında basılan üç katman (PNG, GD ile çizilir).
   Çizim favicon.svg ile aynıdır (64 birimlik ızgara). Kullanım: app-icon.php?s=180 | 192 | 512 */
$s=(int)($_GET['s']??192); if(!in_array($s,[180,192,512],true)) $s=192;
if(!function_exists('imagecreatetruecolor')){ http_response_code(404); exit; }

$K=4; $S=$s*$K; $im=imagecreatetruecolor($S,$S);   // 4 kat büyük çizip küçültünce kenarlar yumuşar
function rgb($im,$hex){ return imagecolorallocate($im,hexdec(substr($hex,1,2)),hexdec(substr($hex,3,2)),hexdec(substr($hex,5,2))); }
// 64'lük ızgaradaki noktayı piksele çevirir. Çizim ortalanır ve %80'e küçültülür: yuvarlak maskeli simgelerde kesilmez.
function ux($v){ global $S; return (int)round((32+($v-32)*0.8)*$S/64); }
function uy($v){ global $S; return (int)round((32+($v-30.5)*0.8)*$S/64); }
function rr($im,$x,$y,$w,$h,$r,$c){
  global $S; $x1=ux($x); $y1=uy($y); $x2=ux($x+$w); $y2=uy($y+$h); $p=max(1,(int)round($r*0.8*$S/64));
  imagefilledrectangle($im,$x1+$p,$y1,$x2-$p,$y2,$c); imagefilledrectangle($im,$x1,$y1+$p,$x2,$y2-$p,$c);
  foreach([[$x1+$p,$y1+$p],[$x2-$p,$y1+$p],[$x1+$p,$y2-$p],[$x2-$p,$y2-$p]] as $q) imagefilledellipse($im,$q[0],$q[1],2*$p,2*$p,$c);
}

imagefilledrectangle($im,0,0,$S,$S,rgb($im,'#1b1931'));
$cyan=rgb($im,'#53dffc');
rr($im,24,8,16,9,2,$cyan);                                                        // nozul gövdesi
imagefilledpolygon($im,[ux(27),uy(16.5),ux(37),uy(16.5),ux(34),uy(25),ux(30),uy(25)],$cyan);   // nozul ucu
rr($im,20,27,15,7,3.5,rgb($im,'#ffe7ba'));                                        // basılmakta olan katman
rr($im,15,36.5,34,7,3.5,rgb($im,'#ffc531'));
rr($im,10,46,44,7,3.5,rgb($im,'#ff4f7b'));

$out=imagecreatetruecolor($s,$s); imagecopyresampled($out,$im,0,0,0,0,$s,$s,$S,$S);
header('Content-Type: image/png'); header('Cache-Control: public, max-age=604800');
imagepng($out);
