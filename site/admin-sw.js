/* Atölye paneli uygulaması için service worker.
   Hiçbir sayfayı önbelleğe almaz (stok ve satış bilgisi hep güncel gelir);
   sadece internet yokken boş ekran yerine bir uyarı gösterir. */
var OFFLINE = '<!doctype html><html lang="tr"><head><meta charset="utf-8">' +
  '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Bağlantı yok</title>' +
  '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f2f4f5;color:#14181c;' +
  'font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;text-align:center;padding:24px}' +
  'button{margin-top:14px;background:#0b7a75;color:#fff;border:0;border-radius:4px;padding:10px 18px;font:inherit}</style></head>' +
  '<body><div><h1 style="font-size:1.3rem;margin:0 0 6px">İnternet bağlantısı yok</h1>' +
  '<p style="margin:0;color:#5b6670">Panel, bilgileri sunucudan okuduğu için bağlantı gerekiyor.</p>' +
  '<button onclick="location.reload()">Tekrar dene</button></div></body></html>';

self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', function (e) {
  var r = e.request;
  if (r.mode !== 'navigate' || r.method !== 'GET' || r.url.indexOf('csv=') > -1) return;
  e.respondWith(fetch(r).catch(function () {
    return new Response(OFFLINE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
  }));
});
