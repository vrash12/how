// Service Worker for PatientCare
const CACHE_NAME = 'patient-care-v1';
const OFFLINE_URL = '/offline.html';

// Resources to cache initially
const INITIAL_CACHED_RESOURCES = [
  '/',
  '/offline.html',
  '/css/app.css',
  '/js/app.js',
  '/js/offline-manager.js',
  '/images/logo.png',
  // Add other critical assets
];

// Install event - cache basic resources
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Caching initial resources');
        return cache.addAll(INITIAL_CACHED_RESOURCES);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.filter((cacheName) => {
          return cacheName.startsWith('patient-care-') && cacheName !== CACHE_NAME;
        }).map((cacheName) => {
          return caches.delete(cacheName);
        })
      );
    })
  );
});

// Fetch event - network-first strategy with offline fallback
self.addEventListener('fetch', (event) => {
  // Skip POST requests - we'll handle them in our application code
  if (event.request.method !== 'GET') return;

  // For API requests, try network then fallback to offline handling
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(JSON.stringify({
          error: 'You are offline',
          offlineMode: true
        }), {
          headers: { 'Content-Type': 'application/json' }
        });
      })
    );
    return;
  }

  // For page requests, use network first approach
  event.respondWith(
    fetch(event.request)
      .catch(() => {
        return caches.match(event.request)
          .then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // If it's a navigation request and we're offline, show offline page
            if (event.request.mode === 'navigate') {
              return caches.match(OFFLINE_URL);
            }
            // Otherwise return a simple offline message
            return new Response('Offline content not available');
          });
      })
  );
});

// Listen for messages from clients
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    event.waitUntil(
      caches.delete(CACHE_NAME).then(() => {
        console.log('Cache cleared successfully');
      })
    );
  }
});