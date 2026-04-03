// Ganti ke v3 agar iPhone terpaksa download versi baru
const CACHE_NAME = 'absenngaji-v3';

const urlsToCache = [
  '/',
  '/manifest.json',
  '/style_mobile.css',
  '/icon-192.png',
  '/icon-512.png'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache);
    })
  );
});

// Strategi Fetch yang AMAN untuk iPhone
self.addEventListener('fetch', event => {
  // JIKA yang diakses adalah file .php, JANGAN lewat cache (Langsung ke Internet)
  // Ini kunci agar redirect login tidak error di Safari
  if (event.request.url.includes('.php')) {
    return; // Biarkan browser menangani secara normal
  }

  // Selain file .php (seperti gambar/css), gunakan cache jika ada
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});

// Bersihkan cache lama
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
      );
    })
  );
});