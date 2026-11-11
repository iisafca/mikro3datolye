/* Atölye paneli · Reels sekmesi: ürün videosunu 9:16 Reels'e çevirir (kesim, hız, kadraj, açılış yazısı,
   marka şeridi, site adresli kapanış kartı). Video tarayıcıda işlenir, sunucuya yüklenmez. */
(function(){
  'use strict';
  var cv=document.getElementById('cv'); if(!cv) return;
  var W=1080,H=1920, ctx=cv.getContext('2d'), vid=document.getElementById('vid');
  var $=function(id){ return document.getElementById(id); };
  var S={loaded:false,start:0,end:0,dur:0,speed:1,fit:'cover',zoom:1,posY:50,hookDur:3,endDur:2.5};
  var playing=false, rendering=false, phase='video', endStart=0, url=null, outBlob=null, outExt='mp4', outName='', rec=null;
  var actx=null, asrc=null, adest=null;
  var C={bg:'#121028',deep:'#0d0b22',ink:'#e4dfff',cream:'#ffe7ba',sun:'#ffc531',cyan:'#53dffc',pink:'#ff4f7b',dark:'#1b1931'};
  var DISP='"Barlow Condensed","Arial Narrow",sans-serif', BODY='"Plus Jakarta Sans",sans-serif';
  var fmt=function(x,d){ return (+x).toLocaleString('tr-TR',{minimumFractionDigits:d==null?1:d,maximumFractionDigits:d==null?1:d}); };
  var up=function(s){ return String(s||'').toLocaleUpperCase('tr-TR'); };
  var val=function(id){ return $(id).value.trim(); };

  /* ---------- Çizim ---------- */
  function rr(x,y,w,h,r){ ctx.beginPath(); ctx.moveTo(x+r,y); ctx.arcTo(x+w,y,x+w,y+h,r); ctx.arcTo(x+w,y+h,x,y+h,r); ctx.arcTo(x,y+h,x,y,r); ctx.arcTo(x,y,x+w,y,r); ctx.closePath(); }
  function logo(x,y,size){
    var s=size/48; ctx.save(); ctx.translate(x-8*s,y-6*s); ctx.scale(s,s);
    ctx.fillStyle=C.cyan; rr(24,8,16,9,2); ctx.fill();
    ctx.beginPath(); ctx.moveTo(27,16.5); ctx.lineTo(37,16.5); ctx.lineTo(34,25); ctx.lineTo(30,25); ctx.closePath(); ctx.fill();
    ctx.fillStyle=C.cream; rr(20,27,15,7,3.5); ctx.fill();
    ctx.fillStyle=C.sun; rr(15,36.5,34,7,3.5); ctx.fill();
    ctx.fillStyle=C.pink; rr(10,46,44,7,3.5); ctx.fill();
    ctx.restore();
  }
  function wrap(text,maxW){
    var words=String(text).split(/\s+/), lines=[], cur='';
    words.forEach(function(w){ var t=cur?cur+' '+w:w; if(ctx.measureText(t).width>maxW && cur){ lines.push(cur); cur=w; } else cur=t; });
    if(cur) lines.push(cur); return lines;
  }
  function ease(t){ t=Math.max(0,Math.min(1,t)); return 1-Math.pow(1-t,3); }
  function drawPlaceholder(){
    var g=ctx.createLinearGradient(0,0,W,H); g.addColorStop(0,'#2c2a46'); g.addColorStop(1,'#121028'); ctx.fillStyle=g; ctx.fillRect(0,0,W,H);
    ctx.fillStyle='rgba(228,223,255,.06)'; for(var y=40;y<H;y+=60) for(var x=40;x<W;x+=60){ ctx.beginPath(); ctx.arc(x,y,3,0,7); ctx.fill(); }
    ctx.fillStyle='rgba(228,223,255,.55)'; ctx.font='600 46px '+BODY; ctx.textAlign='center';
    ctx.fillText('Videonuz burada görünecek',W/2,H/2+20); ctx.textAlign='left';
  }
  function drawVideo(){
    var vw=vid.videoWidth, vh=vid.videoHeight; if(!vw||!vh) return drawPlaceholder();
    function put(sc,py){ var dw=vw*sc, dh=vh*sc; ctx.drawImage(vid,(W-dw)/2,(H-dh)*py/100,dw,dh); }
    if(S.fit==='contain'){
      ctx.save(); if('filter' in ctx) ctx.filter='blur(40px) brightness(0.5)'; put(Math.max(W/vw,H/vh)*1.15,50); ctx.restore();
      if(!('filter' in ctx)){ ctx.fillStyle='rgba(18,16,40,.55)'; ctx.fillRect(0,0,W,H); }
      put(Math.min(W/vw,H/vh)*S.zoom,S.posY);
    } else put(Math.max(W/vw,H/vh)*S.zoom,S.posY);
  }
  function drawHook(t){
    var text=up(val('hook')); if(!text) return;
    var a=ease(t/0.35), out=ease((S.hookDur-t)/0.3);
    ctx.save(); ctx.globalAlpha=Math.min(a,out);
    ctx.font='800 118px '+DISP;
    var lines=wrap(text,840), lh=112, pad=34, bw=0;
    lines.forEach(function(l){ bw=Math.max(bw,ctx.measureText(l).width); });
    var bh=lines.length*lh+pad*1.4, cy=330+bh/2;
    ctx.translate(W/2,cy); ctx.rotate(-0.035); ctx.scale(0.86+0.14*a,0.86+0.14*a);
    ctx.fillStyle=C.deep; rr(-bw/2-pad+12,-bh/2+12,bw+pad*2,bh,22); ctx.fill();
    ctx.fillStyle=C.sun; rr(-bw/2-pad,-bh/2,bw+pad*2,bh,22); ctx.fill();
    ctx.fillStyle=C.dark; ctx.textAlign='center'; ctx.textBaseline='middle';
    lines.forEach(function(l,i){ ctx.fillText(l,0,-bh/2+pad*0.7+lh*i+lh/2+4); });
    ctx.restore();
  }
  function drawBrand(){
    var sub=val('sub'), x=64, y=1330;
    ctx.save();
    var g=ctx.createLinearGradient(0,y-80,0,H); g.addColorStop(0,'rgba(13,11,34,0)'); g.addColorStop(.45,'rgba(13,11,34,.55)'); g.addColorStop(1,'rgba(13,11,34,.75)');
    ctx.fillStyle=g; ctx.fillRect(0,y-80,W,H-y+80);
    logo(x,y,64);
    ctx.fillStyle=C.cream; ctx.font='800 54px '+DISP; ctx.textBaseline='middle';
    ctx.fillText('MİKRO 3D ATÖLYE',x+84,y+33);
    if(sub){ ctx.fillStyle=C.ink; ctx.font='600 38px '+BODY; ctx.shadowColor='rgba(0,0,0,.5)'; ctx.shadowBlur=10; ctx.fillText(wrap(sub,800)[0],x,y+118); }
    ctx.restore();
  }
  function drawEnd(t){
    var a=ease(t/0.35); ctx.save(); ctx.globalAlpha=a;
    ctx.fillStyle='rgba(18,16,40,.9)'; ctx.fillRect(0,0,W,H);
    ctx.textAlign='center'; ctx.textBaseline='middle';
    logo(W/2-110,420-60*(1-a),220);
    ctx.fillStyle=C.cream; ctx.font='800 104px '+DISP;
    var lines=wrap(up(val('pname')||'Size özel üretiyoruz'),900); lines.slice(0,2).forEach(function(l,i){ ctx.fillText(l,W/2,800+i*100); });
    var y0=800+Math.min(2,lines.length)*100+90;
    ctx.fillStyle=C.cyan; ctx.font='700 42px '+BODY; ctx.fillText('SİPARİŞ İÇİN',W/2,y0);
    var site=val('site'), fs=110; ctx.font='800 '+fs+'px '+DISP;
    var sw=ctx.measureText(site).width; if(sw>940){ fs=Math.floor(fs*940/sw); ctx.font='800 '+fs+'px '+DISP; }
    ctx.fillStyle=C.sun; ctx.fillText(site,W/2,y0+110);
    ctx.fillStyle=C.pink; ctx.font='700 56px '+BODY; ctx.fillText(val('handle'),W/2,y0+240);
    ctx.restore();
  }
  function segLen(){ return Math.max(0,(S.end-S.start)/S.speed); }
  function total(){ return S.loaded ? segLen()+($('endOn').checked?S.endDur:0) : 4; }
  function outT(now){
    if(!S.loaded) return (now/1000)%4;
    return phase==='end' ? segLen()+(now-endStart)/1000 : Math.max(0,(vid.currentTime-S.start)/S.speed);
  }
  function draw(now){
    ctx.fillStyle=C.bg; ctx.fillRect(0,0,W,H);
    if(S.loaded && vid.readyState>=2) drawVideo(); else drawPlaceholder();
    var t=outT(now);
    if(phase!=='end'||!S.loaded){ drawBrand(); if(t<S.hookDur) drawHook(t); }
    if(S.loaded && phase==='end') drawEnd((now-endStart)/1000);
    $('tm').textContent=fmt(Math.min(t,total()))+' / '+fmt(total())+' sn';
  }

  /* ---------- Oynatma ve oluşturma döngüsü ---------- */
  function seekStart(){ phase='video'; vid.currentTime=S.start; if(playing||rendering) vid.play().catch(function(){}); }
  function tick(now){
    if(S.loaded && (playing||rendering)){
      if(phase==='video' && (vid.currentTime>=S.end-0.03 || vid.ended)){
        vid.pause();
        if($('endOn').checked){ phase='end'; endStart=now; }
        else if(rendering) finish(); else seekStart();
      } else if(phase==='end' && now-endStart>=S.endDur*1000){
        if(rendering) finish(); else seekStart();
      }
      if(rendering) $('progI').style.width=(Math.min(1,outT(now)/total())*100).toFixed(1)+'%';
    }
    draw(now); requestAnimationFrame(tick);
  }

  /* ---------- Video seçme ---------- */
  var curFile=null;
  function load(file){
    if(!file) return;
    if(!/^video\//.test(file.type) && !/\.(mp4|mov|m4v|webm|3gp)$/i.test(file.name)){ $('finfo').textContent='Bu bir video dosyası değil. MP4 ya da MOV seçin.'; return; }
    stopPlay(); if(url) URL.revokeObjectURL(url); url=URL.createObjectURL(file); curFile=file;
    S.loaded=false; $('fname').textContent=file.name; $('finfo').textContent='Okunuyor…'; $('mState').textContent='Okunuyor…';
    vid.src=url; vid.load();
  }
  vid.addEventListener('loadedmetadata',function(){
    S.dur=vid.duration||0; if(!isFinite(S.dur)||S.dur<=0){ $('finfo').textContent='Videonun süresi okunamadı. Başka bir dosya deneyin.'; return; }
    S.start=0; S.end=Math.min(S.dur,15); S.loaded=true; phase='video';
    ['start','end'].forEach(function(id){ var r=$(id); r.max=S.dur.toFixed(1); r.disabled=false; });
    $('start').value=0; $('end').value=S.end.toFixed(1);
    var mb=curFile?curFile.size/1048576:0;
    $('finfo').textContent=fmt(S.dur)+' sn · '+vid.videoWidth+'×'+vid.videoHeight+(mb?' · '+fmt(mb)+' MB':'')+(vid.videoWidth>vid.videoHeight?' · yatay video: "Sığdır" seçildi':'');
    if(vid.videoWidth>vid.videoHeight) setSeg('fit','contain');
    $('play').disabled=false; $('render').disabled=false; $('rmsg').textContent='Önizlemeyi kontrol edip oluşturun.';
    vid.currentTime=S.start; ui();
  });
  vid.addEventListener('error',function(){ if(!vid.getAttribute('src')) return; $('finfo').textContent='Bu video tarayıcıda açılamadı. Telefon kamerasında "En Uyumlu" (H.264) biçimini seçip tekrar deneyin.'; $('mState').textContent='Açılamadı'; });
  $('file').addEventListener('change',function(){ load(this.files[0]); });
  var drop=$('drop');
  ['dragenter','dragover'].forEach(function(e){ drop.addEventListener(e,function(ev){ ev.preventDefault(); drop.classList.add('over'); }); });
  ['dragleave','drop'].forEach(function(e){ drop.addEventListener(e,function(ev){ ev.preventDefault(); drop.classList.remove('over'); }); });
  drop.addEventListener('drop',function(ev){ var f=ev.dataTransfer.files[0]; if(f) load(f); });

  /* ---------- Ayarlar ---------- */
  function ui(){
    $('startO').textContent=fmt(S.start)+' sn'; $('endO').textContent=fmt(S.end)+' sn';
    $('zoomO').textContent='%'+Math.round(S.zoom*100); $('posYO').textContent=S.posY<35?'üst':S.posY>65?'alt':'orta';
    if(!S.loaded) return;
    var tt=total(), len=segLen();
    $('mDur').textContent=fmt(tt)+' sn'; $('mDur').className=tt>=5&&tt<=20?'pos':'neg';
    $('mState').textContent=len<2?'Kesim çok kısa':tt>30?'Uzun: kısaltmayı düşünün':tt>20?'Biraz uzun':'İyi süre';
  }
  $('start').addEventListener('input',function(){ S.start=Math.min(+this.value,S.end-0.5); this.value=S.start; phase='video'; vid.currentTime=S.start; ui(); });
  $('end').addEventListener('input',function(){ S.end=Math.max(+this.value,S.start+0.5); this.value=S.end; phase='video'; vid.currentTime=Math.max(S.start,S.end-0.4); ui(); });
  $('zoom').addEventListener('input',function(){ S.zoom=+this.value; ui(); });
  $('posY').addEventListener('input',function(){ S.posY=+this.value; ui(); });
  $('endOn').addEventListener('change',ui);
  function setSeg(id,v){ $(id).querySelectorAll('button').forEach(function(b){ b.setAttribute('aria-pressed',String(b.dataset.v===v)); }); if(id==='speed'){ S.speed=+v; vid.playbackRate=S.speed; } else S.fit=v; ui(); }
  ['speed','fit'].forEach(function(id){ $(id).addEventListener('click',function(e){ var b=e.target.closest('button'); if(b&&!rendering) setSeg(id,b.dataset.v); }); });
  $('showSafe').addEventListener('change',function(){ $('safe').classList.toggle('on',this.checked); });
  function stopPlay(){ playing=false; vid.pause(); $('play').textContent='Oynat'; }
  $('play').addEventListener('click',function(){
    if(rendering) return;
    if(playing){ stopPlay(); return; }
    playing=true; this.textContent='Durdur'; vid.muted=true; vid.playbackRate=S.speed;
    if(phase==='end'||vid.currentTime<S.start||vid.currentTime>=S.end-0.05) seekStart(); else vid.play().catch(function(){});
  });

  /* ---------- Oluşturma ---------- */
  function pickMime(){
    if(!window.MediaRecorder||!cv.captureStream) return null;
    var list=['video/mp4;codecs=avc1.42E01E,mp4a.40.2','video/mp4;codecs=avc1','video/mp4','video/webm;codecs=vp9,opus','video/webm;codecs=vp8,opus','video/webm'];
    for(var i=0;i<list.length;i++) if(MediaRecorder.isTypeSupported(list[i])) return list[i];
    return null;
  }
  function ensureAudio(){
    if(actx) return true;
    try{ var AC=window.AudioContext||window.webkitAudioContext; actx=new AC(); asrc=actx.createMediaElementSource(vid); adest=actx.createMediaStreamDestination(); asrc.connect(adest); return true; }
    catch(e){ actx=null; return false; }
  }
  function waitSeek(t){ return new Promise(function(res){ var done=function(){ vid.removeEventListener('seeked',done); res(); }; vid.addEventListener('seeked',done); vid.currentTime=t; setTimeout(done,1500); }); }
  var lockIds=['render','play','start','end','file'];
  $('render').addEventListener('click',async function(){
    var mime=pickMime();
    if(!mime){ $('rmsg').innerHTML='<span class="neg">Bu tarayıcı video kaydedemiyor. Bilgisayarda Chrome ya da telefonda Safari ile açın.</span>'; return; }
    stopPlay(); rendering=true; outBlob=null; $('result').hidden=true; $('prog').hidden=false; $('progI').style.width='0';
    lockIds.forEach(function(id){ $(id).disabled=true; });
    $('rmsg').textContent='Oluşturuluyor… ekranı açık tutun.';
    var stream=cv.captureStream(30), withAudio=$('audio').checked && ensureAudio();
    if(withAudio){ try{ await actx.resume(); }catch(e){} adest.stream.getAudioTracks().forEach(function(t){ stream.addTrack(t); }); vid.muted=false; }
    else vid.muted=true;
    outExt=/mp4/.test(mime)?'mp4':'webm';
    var chunks=[]; rec=new MediaRecorder(stream,{mimeType:mime,videoBitsPerSecond:8000000});
    rec.ondataavailable=function(e){ if(e.data&&e.data.size) chunks.push(e.data); };
    rec.onstop=function(){ outBlob=new Blob(chunks,{type:mime.split(';')[0]}); done(); };
    vid.playbackRate=S.speed; phase='video'; await waitSeek(S.start);
    rec.start(250);
    try{ await vid.play(); }catch(e){ rendering=false; rec.stop(); $('rmsg').innerHTML='<span class="neg">Video oynatılamadı. Tekrar deneyin.</span>'; }
  });
  function finish(){ rendering=false; vid.pause(); vid.muted=true; if(rec&&rec.state!=='inactive') rec.stop(); }
  function slug(s){ return (s||'urun').toLocaleLowerCase('tr-TR').replace(/[çğıöşü]/g,function(c){ return {'ç':'c','ğ':'g','ı':'i','ö':'o','ş':'s','ü':'u'}[c]; }).replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')||'urun'; }
  function done(){
    lockIds.forEach(function(id){ $(id).disabled=!S.loaded&&id!=='file'; });
    $('prog').hidden=true; phase='video'; vid.currentTime=S.start;
    if(!outBlob||!outBlob.size){ $('rmsg').innerHTML='<span class="neg">Kayıt boş çıktı. Tekrar deneyin.</span>'; return; }
    outName='mikro3d-reels-'+slug(val('pname'))+'.'+outExt;
    var o=$('out'); if(o.dataset.u) URL.revokeObjectURL(o.dataset.u); o.dataset.u=URL.createObjectURL(outBlob); o.src=o.dataset.u;
    $('result').hidden=false; $('rmsg').textContent='Reels hazır.';
    $('outInfo').textContent='Hazır · '+fmt(total())+' sn · '+fmt(outBlob.size/1048576)+' MB · '+outExt.toUpperCase();
    var f=new File([outBlob],outName,{type:outBlob.type}), canShare=!!(navigator.canShare&&navigator.canShare({files:[f]}));
    $('share').hidden=!canShare;
    $('saveMsg').innerHTML=outExt==='mp4'
      ?(canShare?'<b>Paylaş</b> ile doğrudan Instagram\'a gönderebilir ya da kaydedip + → Reels ile yükleyebilirsiniz.':'Kaydettikten sonra Instagram\'da + → Reels ile yükleyin.')
      :'<span class="neg">Bu tarayıcı yalnızca WEBM üretebildi; Instagram bunu kabul etmeyebilir.</span> Güncel Chrome ya da telefonda Safari MP4 üretir.';
  }
  $('again').addEventListener('click',function(){ $('render').click(); });
  $('save').addEventListener('click',function(){
    if(!outBlob) return;
    var a=document.createElement('a'); a.href=$('out').dataset.u; a.download=outName; document.body.appendChild(a); a.click(); a.remove();
    $('saveMsg').innerHTML='<span class="pos">İndirildi: '+outName+'</span> Instagram\'da + → Reels ile yükleyin.';
  });
  $('share').addEventListener('click',async function(){
    if(!outBlob) return;
    try{ await navigator.share({files:[new File([outBlob],outName,{type:outBlob.type})],title:val('pname')||'Mikro 3D Atölye'}); }
    catch(e){ if(e&&e.name!=='AbortError') $('saveMsg').textContent='Paylaşılamadı; "Videoyu indir" ile kaydedip yükleyin.'; }
  });

  /* ---------- Açıklama ---------- */
  var TAGS={genel:'#kişiyeözelhediye #hediyelik #3dbaskı #isimlik #bursa #mikro3datolye',
    ev:'#evdekorasyonu #masadekoru #dekoratifaydınlatma #3dbaskı #bursa #mikro3datolye',
    bebek:'#mevlüthediyeliği #bebekhediyelikleri #sünnethediyeliği #babyshower #kişiyeözelhediye #bursa #mikro3datolye',
    ogretmen:'#öğretmenlergünü #öğretmenhediyesi #isimlik #kişiyeözelhediye #bursa #mikro3datolye'};
  var Q={genel:'Sizce en çok kime yakışır? Yorumlara yazın 👇',ev:'Sizce hangi odaya daha çok yakışır? 👇',bebek:'Sizin minik için hangi renk olsun? Yorumlara yazın 👇',ogretmen:'Öğretmeninizin adı ne olsun? Yorumlara yazın 👇'};
  var capEdited=false;
  function caption(){
    if(capEdited) return;
    var tp=$('topic').value, name=val('pname')||'Yeni ürünümüz', hook=val('hook'), f=val('feat');
    $('cap').value=(hook?hook+' ✨\n':'')+name+' atölyemizde 3D baskıyla, size özel üretiliyor.\n\n'
      +(f?'✨ '+f+'\n':'')+'📦 Türkiye\'nin her yerine kargo\n\n'+Q[tp]+'\nSipariş: '+(val('site')||'mikro3datolye.com.tr')+' (profildeki link)\n\n'+TAGS[tp];
  }
  ['topic','feat','pname','hook','site'].forEach(function(id){ $(id).addEventListener('input',caption); $(id).addEventListener('change',caption); });
  $('cap').addEventListener('input',function(){ capEdited=true; });
  $('capReset').addEventListener('click',function(){ capEdited=false; caption(); });
  $('copy').addEventListener('click',function(){
    var t=$('cap').value, m=$('copyMsg'), fb=function(){ $('cap').select(); m.textContent='Metin seçildi; kopyalayın.'; };
    try{ navigator.clipboard.writeText(t).then(function(){ m.textContent='Kopyalandı.'; },fb); }catch(e){ fb(); }
  });

  /* ---------- Başlat ---------- */
  caption(); ui();
  var fonts=['800 100px "Barlow Condensed"','600 40px "Plus Jakarta Sans"','700 40px "Plus Jakarta Sans"'];
  Promise.all(fonts.map(function(f){ return document.fonts?document.fonts.load(f,'İŞĞÜÖÇ'):null; })).catch(function(){}).then(function(){ requestAnimationFrame(tick); });
})();
