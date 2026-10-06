// Service Worker for Academic Management App
const CACHE_NAME = 'academic-app-v3';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Do NOT intercept dynamic PHP pages or API calls
  if (url.pathname.includes('/api/') || url.pathname.endsWith('.php') || url.pathname === '/') {
    return;
  }

  // Handle static assets with graceful fallback
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(event.request).catch((err) => {
        // Return a safe fallback rather than rejecting the promise
        return new Response('', { status: 404, statusText: 'Not Found' });
      });
    }).catch(() => {
      return new Response('', { status: 404, statusText: 'Not Found' });
    })
  );
});
