// VAREEN Academy - Service Worker for PWA
const CACHE_NAME = 'VEREEN-academy-v1.0.0';
const STATIC_CACHE = 'VEREEN-static-v1.0.0';
const DYNAMIC_CACHE = 'VEREEN-dynamic-v1.0.0';

// Resources to cache immediately
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/about.html',
  '/programs.html',
  '/services.html',
  '/gallery.html',
  '/online-classes.html',
  '/apply.html',
  '/contact.html',
  '/assets/css/main.css',
  '/assets/js/main.js',
  '/images/main-logo.png',
  '/images/icon-192x192.png',
  '/images/icon-512x512.png',
  '/images/offline-placeholder.png',
  '/images/screenshot-wide.png',
  '/images/screenshot-narrow.png',
  '/manifest.json',
  '/offline.html'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
            console.log('[SW] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin) return;

  // Skip API request handling in static-only mode
  if (url.pathname.startsWith('/api/')) {
    // Let the request fail or be handled by the client-side; return network response where possible
    return;
  }

  // Handle navigation requests
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Cache successful navigation responses
          if (response.ok) {
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE)
              .then((cache) => cache.put(request, responseClone));
          }
          return response;
        })
        .catch(() => {
          // Return offline page for navigation failures
          return caches.match('/offline.html') || caches.match('/index.html');
        })
    );
    return;
  }

  // Handle other requests
  event.respondWith(
    caches.match(request)
      .then((response) => {
        if (response) {
          return response;
        }

        return fetch(request)
          .then((response) => {
            // Don't cache non-successful responses
            if (!response.ok) {
              return response;
            }

            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE)
              .then((cache) => cache.put(request, responseClone));

            return response;
          })
          .catch(() => {
            // Return offline fallback for images
            if (request.destination === 'image') {
              return caches.match('/images/offline-placeholder.png');
            }
          });
      })
  );
});

// Background sync for form submissions
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync triggered:', event.tag);

  if (event.tag === 'contact-form-sync') {
    event.waitUntil(syncContactForm());
  }

  if (event.tag === 'application-form-sync') {
    event.waitUntil(syncApplicationForm());
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  console.log('[SW] Push notification received');

  let data = {};
  if (event.data) {
    data = event.data.json();
  }

  const options = {
    body: data.body || 'You have a new notification from VAREEN Academy',
    icon: '/images/icon-192x192.png',
    badge: '/images/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: data.primaryKey || 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View Details',
        icon: '/images/icon-72x72.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/images/icon-72x72.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(
      data.title || 'VAREEN Academy',
      options
    )
  );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked:', event.action);

  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow(event.notification.data.url || '/')
    );
  } else {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Helper function to sync contact form
async function syncContactForm() {
  try {
    const cache = await caches.open(DYNAMIC_CACHE);
    const keys = await cache.keys();

    const contactRequests = keys.filter(request =>
      request.url.includes('/api/contact.php')
    );

    for (const request of contactRequests) {
      try {
        const response = await fetch(request);
        if (response.ok) {
          await cache.delete(request);
          console.log('[SW] Synced contact form submission');
        }
      } catch (error) {
        console.error('[SW] Failed to sync contact form:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Error in contact form sync:', error);
  }
}

// Helper function to sync application form
async function syncApplicationForm() {
  try {
    const cache = await caches.open(DYNAMIC_CACHE);
    const keys = await cache.keys();

    const applicationRequests = keys.filter(request =>
      request.url.includes('/api/apply.php')
    );

    for (const request of applicationRequests) {
      try {
        const response = await fetch(request);
        if (response.ok) {
          await cache.delete(request);
          console.log('[SW] Synced application form submission');
        }
      } catch (error) {
        console.error('[SW] Failed to sync application form:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Error in application form sync:', error);
  }
}

// Periodic background sync for cache cleanup
self.addEventListener('periodicsync', (event) => {
  if (event.tag === 'cache-cleanup') {
    event.waitUntil(cleanupCache());
  }
});

// Cache cleanup function
async function cleanupCache() {
  try {
    const cache = await caches.open(DYNAMIC_CACHE);
    const keys = await cache.keys();

    // Remove old entries (older than 1 day)
    const oneDayAgo = Date.now() - (24 * 60 * 60 * 1000);

    for (const request of keys) {
      const response = await cache.match(request);
      if (response) {
        const date = response.headers.get('date');
        if (date && new Date(date).getTime() < oneDayAgo) {
          await cache.delete(request);
        }
      }
    }

    console.log('[SW] Cache cleanup completed');
  } catch (error) {
    console.error('[SW] Error in cache cleanup:', error);
  }
}


