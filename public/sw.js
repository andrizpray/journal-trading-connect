// Journal Trading Connect — Service Worker
// Strategy: Cache-first for static assets, Network-first for pages

const CACHE_NAME = 'jtc-v1';
const STATIC_CACHE = 'jtc-static-v1';

// Files to cache on install (static assets)
const STATIC_ASSETS = [
    '/',
    '/login',
    '/manifest.json',
    '/pwa-icons/icon-192.png',
    '/pwa-icons/icon-512.png',
];

// Cache CSS/JS after build (dynamic)
const CACHEABLE_EXTENSIONS = ['.css', '.js', '.woff2', '.woff', '.ttf'];

// Install — cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {
                // Silently fail if some assets not available
            });
        })
    );
    self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME && name !== STATIC_CACHE)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch — strategy based on request type
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) {
        // For CDN assets (font-awesome, charts, quill) — cache-first
        if (isCacheableAsset(url.pathname)) {
            event.respondWith(cacheFirst(request));
        }
        return;
    }

    // API calls — always network, no cache
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    // POST/PUT/DELETE — always network
    if (request.method !== 'GET') {
        return;
    }

    // Static assets (CSS, JS, fonts) — cache-first
    if (isCacheableAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // HTML pages — network-first, fallback to cache
    event.respondWith(networkFirst(request));
});

/**
 * Cache-first: cek cache dulu, kalau ga ada baru fetch network
 */
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('Offline', { status: 503 });
    }
}

/**
 * Network-first: coba network dulu, kalau gagal fallback ke cache
 */
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        // Return offline page if available
        const offlinePage = await caches.match('/');
        if (offlinePage) {
            return offlinePage;
        }
        return new Response('Offline', { status: 503 });
    }
}

/**
 * Check if URL is a cacheable static asset
 */
function isCacheableAsset(pathname) {
    return CACHEABLE_EXTENSIONS.some((ext) => pathname.endsWith(ext));
}
