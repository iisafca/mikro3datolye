/* Atölye paneli: dilimlenmiş 3MF dosyasından tabla maliyeti.
   Dosya tarayıcıda okunur, sunucuya yüklenmez. Bambu Studio, OrcaSlicer (slice_info.config)
   ve içinde G-code olan 3MF / düz G-code dosyaları desteklenir. */
(function(){
  'use strict';
  if(!window.DecompressionStream||!window.DOMParser) return;
  function n(v){ v=String(v||'').trim(); if(v.indexOf(',')>-1) v=v.replace(/[.\s]/g,'').replace(',','.'); return parseFloat(v)||0; }
  function tl(x){ return x.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺'; }
  function dec(x){ return (Math.round(x*100)/100).toLocaleString('tr-TR',{maximumFractionDigits:2,useGrouping:false}); }
  function sure(s){ var m=Math.round(s/60), h=Math.floor(m/60); m=m%60; return (h?h+' sa ':'')+m+' dk'; }
  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function low(s){ return String(s||'').toLocaleLowerCase('tr-TR'); }

  /* ---------- ZIP okuyucu (3MF bir ZIP arşividir) ---------- */
  function u16(d,o){ return d.getUint16(o,true); }
  function u32(d,o){ return d.getUint32(o,true); }
  function u64(d,o){ return u32(d,o)+u32(d,o+4)*4294967296; }
  function zipList(buf){
    var d=new DataView(buf), L=buf.byteLength, e=-1, i;
    for(i=L-22;i>=Math.max(0,L-65557);i--){ if(u32(d,i)===0x06054b50){ e=i; break; } }
    if(e<0) throw new Error('zip');
    var cnt=u16(d,e+10), off=u32(d,e+16);
    if(off===0xFFFFFFFF||cnt===0xFFFF){ var l=e-20; if(l>=0&&u32(d,l)===0x07064b50){ var z=u64(d,l+8); if(u32(d,z)===0x06064b50){ cnt=u64(d,z+32); off=u64(d,z+48); } } }
    var out={}, p=off, k;
    for(k=0;k<cnt&&p+46<=L;k++){
      if(u32(d,p)!==0x02014b50) break;
      var m=u16(d,p+10), cs=u32(d,p+20), us=u32(d,p+24), nl=u16(d,p+28), xl=u16(d,p+30), cl=u16(d,p+32), lo=u32(d,p+42);
      var name=new TextDecoder().decode(new Uint8Array(buf,p+46,nl)), x=p+46+nl, xe=x+xl;
      while(x+4<=xe){ var id=u16(d,x), sz=u16(d,x+2), q=x+4;
        if(id===1){ if(us===0xFFFFFFFF){ us=u64(d,q); q+=8; } if(cs===0xFFFFFFFF){ cs=u64(d,q); q+=8; } if(lo===0xFFFFFFFF) lo=u64(d,q); }
        x+=4+sz; }
      out[name.replace(/^\//,'')]={m:m,cs:cs,lo:lo};
      p+=46+nl+xl+cl;
    }
    return out;
  }
  function zipRead(buf,en){
    var d=new DataView(buf);
    if(u32(d,en.lo)!==0x04034b50) return Promise.reject(new Error('zip'));
    var raw=new Uint8Array(buf,en.lo+30+u16(d,en.lo+26)+u16(d,en.lo+28),en.cs);
    if(en.m===0) return Promise.resolve(new TextDecoder().decode(raw));
    if(en.m!==8) return Promise.reject(new Error('method'));
    return new Response(new Blob([raw]).stream().pipeThrough(new DecompressionStream('deflate-raw'))).text();
  }

  /* ---------- Dilimleyici bilgilerini okuma ---------- */
  function meta(el,k){ for(var c=el.firstElementChild;c;c=c.nextElementSibling){ if(c.tagName==='metadata'&&c.getAttribute('key')===k) return c.getAttribute('value'); } return null; }
  function xml(t){ return new DOMParser().parseFromString(t,'application/xml'); }
  function plateNames(t){
    var r={}; xml(t).querySelectorAll('plate').forEach(function(pl){ var id=+meta(pl,'plater_id'), nm=meta(pl,'plater_name'); if(id&&nm) r[id]=nm; }); return r;
  }
  function parseSlice(t,names){
    var out=[];
    xml(t).querySelectorAll('plate').forEach(function(pl){
      var no=+meta(pl,'index')||out.length+1, p={no:no, ad:names[no]||'', sec:n(meta(pl,'prediction')), g:n(meta(pl,'weight')), obj:[], fil:[]};
      pl.querySelectorAll('object').forEach(function(o){ if(o.getAttribute('skipped')!=='true'&&o.getAttribute('name')) p.obj.push(o.getAttribute('name')); });
      pl.querySelectorAll('filament').forEach(function(f){ var g=n(f.getAttribute('used_g')); if(g>0) p.fil.push({type:f.getAttribute('type')||'', color:f.getAttribute('color')||'', g:g}); });
      if(!p.fil.length&&p.g>0) p.fil.push({type:'',color:'',g:p.g});
      if(!p.g) p.fil.forEach(function(f){ p.g+=f.g; });
      if(p.sec>0||p.g>0) out.push(p);
    });
    return out;
  }
  function parseGcode(t){
    var m, sec=0, per=[], ty=[], co=[], r, x;
    m=t.match(/;\s*total filament (?:weight|used) \[g\]\s*[:=]\s*([0-9.,\s]+)/i)||t.match(/;\s*filament used \[g\]\s*=\s*([0-9.,\s]+)/i);
    if(m) per=m[1].split(',').map(function(v){ return parseFloat(v)||0; });
    m=t.match(/total estimated time:\s*([0-9dhms ]+)/i)||t.match(/estimated printing time(?: \(normal mode\))?\s*[:=]\s*([0-9dhms ]+)/i);
    if(m){ r=/(\d+)\s*([dhms])/g; while((x=r.exec(m[1]))) sec+=+x[1]*{d:86400,h:3600,m:60,s:1}[x[2]]; }
    m=t.match(/;\s*filament_type\s*=\s*([^\r\n]*)/i); if(m) ty=m[1].split(';');
    m=t.match(/;\s*(?:filament_colour|filament_color|extruder_colour)\s*=\s*([^\r\n]*)/i); if(m) co=m[1].split(';');
    var p={no:1, ad:'', sec:sec, g:0, obj:[], fil:[]};
    per.forEach(function(g,i){ if(g>0){ p.fil.push({type:(ty[i]||'').trim(), color:(co[i]||'').trim(), g:g}); p.g+=g; } });
    return (p.sec>0||p.g>0)?p:null;
  }
  function readFile(file){
    return file.arrayBuffer().then(function(buf){
      if(/\.gcode$/i.test(file.name)){ var g=parseGcode(new TextDecoder().decode(buf)); return g?[g]:[]; }
      var files=zipList(buf), keys=Object.keys(files), names={};
      function find(re){ return keys.filter(function(k){ return re.test(k); }); }
      var mk=find(/^Metadata\/model_settings\.config$/i)[0], sk=find(/^Metadata\/slice_info\.config$/i)[0];
      return (mk?zipRead(buf,files[mk]).then(function(t){ names=plateNames(t); }).catch(function(){}):Promise.resolve())
        .then(function(){ return sk?zipRead(buf,files[sk]).then(function(t){ return parseSlice(t,names); }):[]; })
        .then(function(pl){
          if(pl.length) return pl;
          var gk=find(/\.gcode$/i), out=[];
          return gk.reduce(function(pr,k){ return pr.then(function(){ return zipRead(buf,files[k]).then(function(t){
            var p=parseGcode(t); if(p){ p.no=+((k.match(/plate_(\d+)/i)||[])[1])||out.length+1; p.ad=names[p.no]||''; out.push(p); } }); }); }, Promise.resolve())
            .then(function(){ return out.sort(function(a,b){ return a.no-b.no; }); });
        });
    });
  }

  /* ---------- Filamenti stoktaki malzemeyle eşleştirme ---------- */
  var RENK=[['beyaz',255,255,255],['siyah',0,0,0],['gri',128,128,128],['gümüş',192,192,192],['kırmızı',210,20,30],['bordo',120,0,30],['pembe',255,140,190],['turuncu',255,130,0],['sarı',255,215,0],['altın',212,175,55],['krem',245,230,200],['bej',220,195,155],['kahve',120,70,30],['yeşil',0,160,60],['mavi',0,90,220],['lacivert',0,20,100],['turkuaz',60,210,200],['mor',120,30,160]];
  function renkAdi(hex){
    var m=/^#?([0-9a-f]{6})/i.exec(hex||''); if(!m) return '';
    var v=parseInt(m[1],16), r=v>>16, g=(v>>8)&255, b=v&255, best='', bd=1e9;
    RENK.forEach(function(c){ var dd=(c[1]-r)*(c[1]-r)+(c[2]-g)*(c[2]-g)+(c[3]-b)*(c[3]-b); if(dd<bd){ bd=dd; best=c[0]; } });
    return best;
  }
  function eslestir(sel,f){
    var ty=low(f.type), rn=renkAdi(f.color), best=null, bs=0;
    if(!sel) return null;
    [].forEach.call(sel.options,function(o){
      if(!o.value) return;
      if(o.dataset.unit&&!/^(g|gr|gram)$/i.test(o.dataset.unit.trim())) return;
      var t=low(o.textContent).replace(/\(kalan[^)]*\)|\([^)]*\)\s*$/g,''), s=0;
      if(ty&&t.indexOf(ty)>-1) s+=2;
      if(rn&&t.indexOf(rn)>-1) s+=1;
      if(s>bs){ bs=s; best=o; }
    });
    return best;
  }

  /* ---------- Ekran ---------- */
  var css='.m3f{display:flex;flex-direction:column;gap:6px;margin:0 0 6px}.m3f .x{margin-top:4px}.m3f table{font-size:.88rem;background:var(--bg)}'
    +'.m3f td,.m3f th{padding:7px 9px}.m3f .fl{display:block;white-space:nowrap}.m3f .act{white-space:nowrap}.m3f tr.tp td{font-weight:700;border-bottom:0}';
  var st=document.createElement('style'); st.textContent=css; document.head.appendChild(st);

  function ad(p,cok){ var a=p.ad||p.obj.slice(0,2).join(', '); return (cok||!a?'Tabla '+p.no:'')+(cok&&a?' · ':'')+(a||''); }

  function kur(f,mod,W,K){
    var box=document.createElement('div'); box.className='full m3f';
    box.innerHTML='<label class="lbl">3MF dosyasından doldur<input type="file" accept=".3mf,.gcode"></label>'
      +'<p class="hint" style="margin:0">Bambu Studio veya OrcaSlicer\'da <b>dilimledikten sonra</b> kaydettiğiniz .3mf dosyasını seçin. Her tablanın süresi, filamenti ve maliyeti hesaplanır; dosya sunucuya yüklenmez.</p><div class="m3o"></div>';
    var anchor=mod==='b'?f.querySelector('.lbl'):f.querySelector('.prow');
    if(!anchor) return;
    anchor.parentNode.insertBefore(box,anchor);
    var inp=box.querySelector('input'), out=box.querySelector('.m3o'), src=f.querySelector('select[name="mat[]"],select[name="pmat[]"]'), pl=[], dosya='';

    inp.addEventListener('change',function(){
      var file=inp.files[0]; if(!file) return;
      dosya=file.name.replace(/(\.gcode)?\.(3mf|gcode)$/i,''); out.innerHTML='<p class="hint">Okunuyor…</p>';
      readFile(file).then(function(r){ pl=r; goster(); }).catch(function(){
        out.innerHTML='<p class="hint neg">Dosya okunamadı. Bambu Studio ya da OrcaSlicer\'dan kaydedilmiş bir .3mf dosyası seçin.</p>'; });
    });

    function goster(){
      if(!pl.length){ out.innerHTML='<p class="hint"><b class="neg">Bu dosyada dilimleme bilgisi yok.</b> Dosya dilimlenmeden kaydedilmiş olabilir. Bambu Studio veya OrcaSlicer\'da önce <b>Dilimle</b>, sonra <b>Kaydet</b> deyin ya da <b>Dışa aktar → Dilimlenmiş tablayı dışa aktar</b> ile .gcode.3mf dosyası alın.</p>'; return; }
      var cok=pl.length>1, T={sec:0,g:0,mc:0,pc:0}, eksik=0;
      var h='<div class="x"><table><tr class="h"><th>Tabla</th><th>Süre</th><th>Filament</th><th>Malzeme</th><th>Elektrik</th><th>Tabla maliyeti</th><th></th></tr>';
      pl.forEach(function(p,i){
        p.h=Math.round(p.sec/36)/100; p.mc=0; p.pc=p.h*W/1000*K;
        var fl=p.fil.map(function(x){ x.o=eslestir(src,x); if(x.o) p.mc+=(+x.o.dataset.uc||0)*x.g; else eksik++;
          return '<span class="fl">'+(x.color?'<span class="sw" style="--c:'+esc(x.color.slice(0,7))+'"></span>':'')+esc(x.type||'Filament')+' '+dec(x.g)+' g <small>→ '+(x.o?esc(x.o.textContent.replace(/\s*\((kalan )?[^)]*\)\s*$/,'')):'<span class="neg">malzeme seçin</span>')+'</small></span>'; }).join('');
        T.sec+=p.sec; T.g+=p.g; T.mc+=p.mc; T.pc+=p.pc;
        h+='<tr><td data-l="Tabla"><b>'+esc(ad(p,cok))+'</b></td><td data-l="Süre">'+sure(p.sec)+'</td><td data-l="Filament">'+fl+'</td><td data-l="Malzeme">'+tl(p.mc)+'</td><td data-l="Elektrik">'+tl(p.pc)+'</td><td data-l="Tabla maliyeti"><b>'+tl(p.mc+p.pc)+'</b></td>'
          +'<td class="act"><button type="button" class="alt" data-i="'+i+'">Forma aktar</button></td></tr>';
      });
      if(cok) h+='<tr class="tp"><td data-l="Toplam">'+pl.length+' tabla</td><td data-l="Süre">'+sure(T.sec)+'</td><td data-l="Filament">'+dec(T.g)+' g</td><td data-l="Malzeme">'+tl(T.mc)+'</td><td data-l="Elektrik">'+tl(T.pc)+'</td><td data-l="Toplam maliyet">'+tl(T.mc+T.pc)+'</td>'
        +'<td class="act"><button type="button" data-i="all">Tümünü aktar</button></td></tr>';
      h+='</table></div><p class="hint" style="margin:0">'+(W&&K?'':'<span class="neg">Elektrik ayarı yok, elektrik 0 hesaplandı.</span> ')
        +(eksik?'Bazı filamentler stoktaki bir malzemeyle eşleşmedi; maliyetleri forma aktardıktan sonra malzemeyi seçince eklenir. ':'Malzemeler tür ve renge göre tahmin edildi; formda kontrol edin. ')
        +'Ambalaj, led gibi diğer giderler tabla maliyetine dahil değildir.</p>';
      out.innerHTML=h;
    }

    out.addEventListener('click',function(e){
      var b=e.target.closest('button[data-i]'); if(!b) return;
      var list=b.dataset.i==='all'?pl:[pl[+b.dataset.i]];
      if(mod==='b') aktarB(list); else aktarR(list);
      b.textContent='Aktarıldı ✓';
    });

    function aktarB(list){
      var E=function(k){ return f.elements.namedItem(k); }, sec=0, fil=[];
      list.forEach(function(p){ sec+=p.sec; p.fil.forEach(function(x){
        var e=x.o?fil.filter(function(y){ return y.o===x.o; })[0]:null; if(e) e.g+=x.g; else fil.push({o:x.o,g:x.g}); }); });
      E('hours').value=dec(Math.round(sec/36)/100);
      if(!E('title').value.trim()) E('title').value=list.length===1&&pl.length>1?dosya+' – '+ad(list[0],true):dosya;
      var rows=f.querySelectorAll('.mrow');
      while(rows.length<fil.length){ var last=rows[rows.length-1], c=last.cloneNode(true), lb=c.querySelector('.lbl');
        if(lb&&lb.firstChild&&lb.firstChild.nodeType===3) lb.firstChild.nodeValue='Malzeme '+(rows.length+1);
        last.parentNode.insertBefore(c,last.nextSibling); rows=f.querySelectorAll('.mrow'); }
      rows.forEach(function(r,i){ var s=r.querySelector('select'), a=r.querySelector('input');
        if(i<fil.length){ s.value=fil[i].o?fil[i].o.value:''; a.value=dec(fil[i].g); } else { s.value=''; a.value=''; } });
      var nt=E('notes'); if(nt&&!nt.value.trim()) nt.value='3MF: '+dosya+(list.length===1&&pl.length>1?', tabla '+list[0].no:'');
      f.dispatchEvent(new Event('input',{bubbles:true}));
    }

    function aktarR(list){
      var cok=pl.length>1, need=[];
      list.forEach(function(p){
        if(!p.fil.length) need.push({t:ad(p,cok),o:null,g:p.g,h:p.h});
        p.fil.forEach(function(x,j){ need.push({t:ad(p,cok)+(p.fil.length>1?' ('+(x.type||'filament')+' '+(j+1)+')':''),o:x.o,g:x.g,h:j===0?p.h:0}); });
      });
      function bos(r){ var i=r.querySelectorAll('input'); return !i[0].value.trim()&&!i[1].value.trim()&&!i[2].value.trim()&&!r.querySelector('select').value; }
      var rows=[].filter.call(f.querySelectorAll('.prow'),bos), all=f.querySelectorAll('.prow');
      while(rows.length<need.length){ var last=all[all.length-1], c=last.cloneNode(true);
        c.querySelectorAll('input').forEach(function(i){ i.value=''; i.removeAttribute('value'); });
        [].forEach.call(c.querySelector('select').options,function(o){ o.removeAttribute('selected'); }); c.querySelector('select').value='';
        last.parentNode.insertBefore(c,last.nextSibling); rows.push(c); all=f.querySelectorAll('.prow'); }
      need.forEach(function(v,k){ var r=rows[k], i=r.querySelectorAll('input');
        i[0].value=v.t; r.querySelector('select').value=v.o?v.o.value:''; i[1].value=dec(v.g); i[2].value=v.h?dec(v.h):''; });
      f.dispatchEvent(new Event('input',{bubbles:true}));
    }
  }

  function basla(){
    var b=document.getElementById('bform');
    if(b) kur(b,'b',+b.dataset.watt||0,+b.dataset.kwh||0);
    var rc=document.querySelector('.rcs'), W=rc?+rc.dataset.watt||0:0, K=rc?+rc.dataset.kwh||0:0;
    document.querySelectorAll('form.rform').forEach(function(f){ kur(f,'r',W,K); });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',basla); else basla();
})();
