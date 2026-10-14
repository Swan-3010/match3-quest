/*
 * First deployment: no offline caching.
 * Registration is scoped to /3Row/.
 */
self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

// Network-only fetch handler: нужен для PWA-установки (Chrome требует SW
// с fetch-обработчиком). Кэширования намеренно нет (см. runbook) —
// запросы проходят напрямую, оффлайн-режим не поддерживается.
self.addEventListener('fetch', (event) => {
  event.respondWith(fetch(event.request));
});
