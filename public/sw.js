/**
 * Match Quest — Service Worker
 * Кэширование статики, offline fallback
 */

const CACHE_NAME = 'match-quest-v1';
const STATIC_ASSETS = [
  '/',
  '/assets/css/app.css',
  '/assets/js/auth.js',
  '/assets/js/api-client.js',
  '/assets/js/app.js',
  '/assets/js/game_engine_pixijs.js',
  '/assets/sounds/match.mp3',
  '/assets/sounds/cascade.mp3',
  '/assets/sounds/win.mp3',
];

// Install — кэшируем статику
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// Activate — чистим старые кэши
self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch — стратегия: сеть first, fallback to cache
self.addEventListener('fetch', (e) => {
  // API requests — не кэшируем
  if (e.request.url.includes('/api/')) return;

  e.respondWith(
    fetch(e.request).then((res) => {
      if (res.ok) {
        const clone = res.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(e.request, clone));
      }
      return res;
    }).catch(() => {
      return caches.match(e.request).then((cached) => {
        return cached || caches.match('/');
      });
    })
  );
});
