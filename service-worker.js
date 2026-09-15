const CACHE_NAME = 'safebadger-v1.0.0';
const ASSETS_TO_CACHE = [
  './',
  './index.php',
  './manifest.webmanifest',
  './assets/css/style.css',
  './assets/js/crypto.js',
  './assets/js/app.js',
  './assets/js/pwa.js',
  './assets/icons/apple-touch-icon.png',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap'
];

// Service Worker Kurulumu
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch((err) => {
        console.warn('Önbellek ekleme uyarısı:', err);
      });
    })
  );
  self.skipWaiting();
});

// Eski Önbellekleri Temizleme
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Ağ veya Önbellekten İstek Getirme (Stale-While-Revalidate yaklaşımı)
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // API çağrılarını asla önbellekten getirme (her zaman taze veri)
  if (url.pathname.includes('/api/')) {
    event.respondWith(fetch(event.request));
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        // Arka planda güncelle
        fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, networkResponse);
            });
          }
        }).catch(() => {});
        return cachedResponse;
      }
      return fetch(event.request);
    })
  );
});
