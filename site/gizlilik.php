<?php
/* Gizlilik ve çerez politikası. Veritabanı gerektirmez, bu yüzden config.php yüklenmez. */
$mail  = 'isafca16@gmail.com';
$ig    = 'https://www.instagram.com/mikro3datolye';
$wa    = 'https://wa.me/905346668102';
// Veri sorumlusu: şahıs işletmesiyse ad soyad, şirketse ticari unvan yazın.
$sorumlu    = 'Mikro 3D Atölye';
$guncelleme = '9 Kasım 2026';
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script>
/* Analiz çerezleri yalnızca ziyaretçi kabul ederse yüklenir. */
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
function loadAnalytics(){
  if (window.__gaLoaded) return; window.__gaLoaded = true;
  var s = document.createElement('script'); s.async = true;
  s.src = 'https://www.googletagmanager.com/gtag/js?id=G-DM8M8T5C9S';
  document.head.appendChild(s);
  gtag('js', new Date()); gtag('config', 'G-DM8M8T5C9S');
}
</script>
<title>Gizlilik ve Çerez Politikası | Mikro 3D Atölye</title>
<meta name="description" content="Mikro 3D Atölye web sitesinde hangi kişisel verilerin ve çerezlerin hangi amaçla kullanıldığı, KVKK kapsamındaki haklarınız ve tercihlerinizi nasıl değiştirebileceğiniz.">
<meta name="theme-color" content="#2a1f5c">
<link rel="stylesheet" href="site.css?v=4">
<style>
.legal{background:var(--paper);padding:56px 0 80px}
.legal .wrap{max-width:820px}
.legal h1{font-size:clamp(1.9rem,5vw,3rem);margin-bottom:12px}
.legal .upd{color:var(--mut);font-size:.95rem;margin-bottom:32px}
.legal h2{font-size:clamp(1.15rem,2.6vw,1.45rem);margin:44px 0 14px;line-height:1.2}
.legal p,.legal li{max-width:68ch}
.legal p+p{margin-top:12px}
.legal ul{padding-left:1.2em;margin:10px 0 0}
.legal li+li{margin-top:6px}
.legal a{text-underline-offset:3px;font-weight:500}
.legal .sum{background:var(--ice);border-left:6px solid var(--coral);border-radius:var(--r);padding:22px 24px}
.legal .sum ul{margin:0}
.tbl{overflow-x:auto;margin-top:14px;border:2px solid var(--line);border-radius:var(--r)}
.tbl table{width:100%;border-collapse:collapse;font-size:.93rem;min-width:620px}
.tbl th,.tbl td{text-align:left;vertical-align:top;padding:12px 14px;border-bottom:1px solid var(--line)}
.tbl tr:last-child td{border-bottom:0}
.tbl th{background:var(--ice);font-weight:700}
.tbl code{font-size:.88em;background:var(--ice);padding:1px 6px;border-radius:6px;white-space:nowrap}
.legal .pref{margin-top:16px;cursor:pointer;font-family:inherit}
.back{display:inline-block;margin-top:44px;font-weight:700}
</style>
</head><body>

<header class="top"><div class="wrap">
  <a class="logo" href="./" aria-label="Mikro 3D Atölye ana sayfa"><span class="mark" aria-hidden="true"><i></i><i></i><i></i></span>mikro3d atölye</a>
  <nav aria-label="Ana menü"><a href="./#urunler">Ürünler</a><a href="./#ozel">Özel sipariş</a><a href="./#atolye">Atölye</a></nav>
</div></header>

<main class="legal"><div class="wrap">
  <h1>Gizlilik ve Çerez Politikası</h1>
  <p class="upd">Son güncelleme: <?= e($guncelleme) ?></p>

  <div class="sum">
    <ul>
      <li>Siteyi gezmek için üye olmanız ya da bilgi vermeniz gerekmez.</li>
      <li>Google Analytics yalnızca siz <b>Kabul et</b> derseniz çalışır.</li>
      <li>Instagram paylaşımları yalnızca siz <b>Paylaşımları göster</b> düğmesine basarsanız yüklenir.</li>
      <li>Reklam çerezi kullanmıyoruz, verilerinizi satmıyoruz.</li>
    </ul>
  </div>

  <h2>1. Veri sorumlusu</h2>
  <p>6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında veri sorumlusu <b><?= e($sorumlu) ?></b>'dür. Bu metinle ilgili sorularınız için <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a> adresine yazabilirsiniz.</p>

  <h2>2. Hangi verileri, neden işliyoruz?</h2>
  <div class="tbl"><table>
    <thead><tr><th>Durum</th><th>Veriler</th><th>Amaç</th><th>Hukuki sebep</th></tr></thead>
    <tbody>
      <tr><td>Sipariş ve iletişim (WhatsApp, e-posta, Instagram mesajı)</td><td>Ad soyad, telefon, e-posta, mesaj içeriği, paylaştığınız fotoğraf ve ölçüler, teslimat adresi</td><td>Talebinizi yanıtlamak, ürünü tasarlayıp üretmek, teslim etmek</td><td>Sözleşmenin kurulması ve ifası (KVKK m. 5/2-c)</td></tr>
      <tr><td>Siteyi ziyaret</td><td>IP adresi, tarayıcı bilgisi, ziyaret zamanı (sunucu kayıtları)</td><td>Sitenin güvenli ve kesintisiz çalışması</td><td>Meşru menfaat ve hukuki yükümlülük (KVKK m. 5/2-ç, 5/2-f)</td></tr>
      <tr><td>Analiz (yalnızca onay verirseniz)</td><td>Çerez kimliği, görüntülenen sayfalar, cihaz ve tarayıcı türü, yaklaşık konum</td><td>Sitenin nasıl kullanıldığını anlayıp geliştirmek</td><td>Açık rıza (KVKK m. 5/1)</td></tr>
    </tbody>
  </table></div>
  <p style="margin-top:14px">Sipariş ve iletişim verilerini, ilişkimiz sürdüğü sürece ve vergi mevzuatının öngördüğü yasal saklama süreleri boyunca saklarız. Süre dolduğunda siler ya da anonim hale getiririz.</p>

  <h2>3. Çerezler ve tarayıcı hafızası</h2>
  <p>Çerez ve tarayıcı hafızası (localStorage), sitenin cihazınıza kaydettiği küçük bilgilerdir. Bu sitede kullanılanların tamamı:</p>
  <div class="tbl"><table>
    <thead><tr><th>Ad</th><th>Sağlayıcı</th><th>Ne işe yarar</th><th>Süre</th><th>Tür</th></tr></thead>
    <tbody>
      <tr><td><code>PHPSESSID</code></td><td>Bu site</td><td>Sitenin teknik olarak çalışması için oturum kimliği. Kişisel bilgi içermez.</td><td>Tarayıcı kapanınca silinir</td><td>Zorunlu</td></tr>
      <tr><td><code>m3d_cerez</code></td><td>Bu site (tarayıcı hafızası)</td><td>Çerez tercihinizi (kabul ya da ret) hatırlar.</td><td>Siz silene kadar</td><td>Zorunlu</td></tr>
      <tr><td><code>m3d_ig</code></td><td>Bu site (tarayıcı hafızası)</td><td>Instagram paylaşımlarını göstermeyi seçtiğinizi hatırlar.</td><td>Siz silene kadar</td><td>İşlevsel</td></tr>
      <tr><td><code>_ga</code></td><td>Google Analytics</td><td>Ziyaretçileri birbirinden ayırt eder.</td><td>2 yıl</td><td>Analiz, yalnızca onayla</td></tr>
      <tr><td><code>_ga_DM8M8T5C9S</code></td><td>Google Analytics</td><td>Ziyaret oturumunu takip eder.</td><td>2 yıl</td><td>Analiz, yalnızca onayla</td></tr>
    </tbody>
  </table></div>

  <h2>4. Üçüncü taraf hizmetler</h2>
  <ul>
    <li><b>Google Analytics (Google LLC):</b> Onay verirseniz ziyaret bilgileri Google'ın sunucularına gönderilir.</li>
    <li><b>Instagram (Meta Platforms):</b> "Paylaşımları göster" düğmesine basarsanız Instagram'ın kodu yüklenir ve Instagram kendi çerezlerini kullanabilir.</li>
    <li><b>WhatsApp, Shopier ve Dolap:</b> Bu bağlantılara tıkladığınızda ilgili hizmetin kendi sitesine ya da uygulamasına geçersiniz. Orada o hizmetin gizlilik politikası geçerlidir.</li>
  </ul>

  <h2>5. Yurt dışına aktarım</h2>
  <p>Google ve Meta'nın sunucuları yurt dışında bulunur. Google Analytics ve Instagram aracılığıyla yapılan aktarımlar, KVKK m. 9 uyarınca yalnızca bu hizmetleri açıkça kabul etmeniz halinde gerçekleşir. Onayınızı istediğiniz zaman geri alabilirsiniz.</p>

  <h2>6. Tercihinizi değiştirme</h2>
  <p>Analiz çerezlerine verdiğiniz onayı istediğiniz zaman geri alabilir ya da yeniden verebilirsiniz. Daha önce yüklenmiş çerezleri silmek için tarayıcınızın ayarlarından bu sitenin verilerini temizleyebilirsiniz.</p>
  <button type="button" class="btn sm pref" id="consent-open">Çerez tercihimi değiştir</button>

  <h2>7. Haklarınız</h2>
  <p>KVKK'nın 11. maddesi uyarınca şu haklara sahipsiniz:</p>
  <ul>
    <li>Kişisel verilerinizin işlenip işlenmediğini öğrenmek ve işlendiyse bilgi talep etmek,</li>
    <li>İşlenme amacını ve amaca uygun kullanılıp kullanılmadığını öğrenmek,</li>
    <li>Yurt içinde ya da yurt dışında aktarıldığı üçüncü kişileri bilmek,</li>
    <li>Eksik ya da yanlış işlenmişse düzeltilmesini istemek,</li>
    <li>KVKK'da öngörülen şartlar çerçevesinde silinmesini ya da yok edilmesini istemek,</li>
    <li>Düzeltme ve silme işlemlerinin, verilerin aktarıldığı üçüncü kişilere bildirilmesini istemek,</li>
    <li>Otomatik sistemlerle analiz edilmesi sonucu aleyhinize bir sonuç çıkmasına itiraz etmek,</li>
    <li>Kanuna aykırı işlenme nedeniyle zarara uğramanız halinde zararın giderilmesini talep etmek.</li>
  </ul>

  <h2>8. Başvuru</h2>
  <p>Haklarınızı kullanmak için <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a> adresine, kimliğinizi doğrulamamıza yetecek bilgilerle birlikte yazabilirsiniz. Başvurunuzu en geç 30 gün içinde ücretsiz olarak yanıtlarız.</p>

  <h2>9. Değişiklikler</h2>
  <p>Bu metni, sitede kullanılan hizmetler değiştikçe güncelleriz. Güncel sürüm her zaman bu sayfadadır, son güncelleme tarihi sayfanın başında yazar.</p>

  <a class="back" href="./">← Ana sayfaya dön</a>
</div></main>

<footer><div class="wrap">
  <h2>Aklınızdaki ürünü birlikte tasarlayalım.</h2>
  <a class="btn light" href="<?= e($wa) ?>" rel="noopener">WhatsApp'tan yazın</a>
  <ul class="links" aria-label="İletişim">
    <li><a href="<?= e($ig) ?>" rel="noopener">Instagram</a></li>
    <li><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></li>
  </ul>
  <p class="copy">© <?= date('Y') ?> Mikro 3D Atölye</p>
</div></footer>

<div class="consent" id="consent" role="region" aria-label="Çerez tercihi" hidden>
  <p>Bu site, ziyaretlerin nasıl kullanıldığını anlamak için Google Analytics çerezleri kullanır. Siz kabul etmeden analiz çerezleri çalışmaz.</p>
  <div class="acts"><button type="button" class="btn sm" id="consent-yes">Kabul et</button><button type="button" class="btn sm ghost" id="consent-no">Reddet</button></div>
</div>
<script>
(function(){
  var KEY='m3d_cerez', box=document.getElementById('consent');
  function get(){ try{ return localStorage.getItem(KEY); }catch(e){ return null; } }
  function set(v){ try{ localStorage.setItem(KEY,v); }catch(e){} }
  function apply(v){ if(v==='evet') loadAnalytics(); box.hidden = (v==='evet'||v==='hayir'); }
  document.getElementById('consent-yes').onclick=function(){ set('evet'); apply('evet'); };
  document.getElementById('consent-no').onclick=function(){ set('hayir'); apply('hayir'); };
  document.getElementById('consent-open').onclick=function(){ box.hidden=false; box.querySelector('button').focus(); };
  apply(get());
})();
</script>
</body></html>
