# PWA Setup for Laravel Blade Apps

Make a Laravel Blade app installable on mobile home screens (Add to Home Screen / Install prompt).

## Steps

### 1. Create manifest.json

Place in `public/manifest.json`:

```json
{
    "name": "App Name",
    "short_name": "APP",
    "description": "Brief description",
    "start_url": "/dashboard",
    "display": "standalone",
    "background_color": "#030712",
    "theme_color": "#06b6d4",
    "orientation": "portrait",
    "icons": [
        { "src": "/pwa-icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
        { "src": "/pwa-icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
    ]
}
```

### 2. Generate Icons

**If ImageMagick is available:** `convert icon.svg -resize 192x192 icon-192.png`

**If not (common on minimal VPS):** Install `librsvg2-bin` for SVG→PNG:
```bash
sudo apt-get install -y librsvg2-bin
rsvg-convert -w 192 -h 192 icon.svg -o icon-192.png
rsvg-convert -w 512 -h 512 icon.svg -o icon-512.png
```

Store in `public/pwa-icons/`. Create icons as simple SVGs with the app's brand colors.

### 3. Add Meta Tags to All Layouts

Add to `layouts/app.blade.php`, `layouts/guest.blade.php`, and any standalone pages (e.g., public profile):

```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#06b6d4">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="APP">
<link rel="apple-touch-icon" href="/pwa-icons/icon-192.png">
```

**Pitfall:** Standalone pages that don't extend a shared layout (e.g., public profile page accessible without auth) need their own PWA meta tags. Don't forget these.

**Pitfall:** `apple-mobile-web-app-capable` may already exist in guest layout from viewport meta. Don't duplicate.

### 4. Verify

- Open in Chrome DevTools → Application tab → Manifest section
- Should show "Installability" checks passing (manifest found, service worker optional, icons present)
- On mobile: browser should show "Add to Home Screen" option in share menu

### 5. Service Worker (Offline Caching)

Place in `public/sw.js`. Use two caching strategies:
- **Cache-first** for static assets (CSS, JS, fonts, icons) — fast, works offline
- **Network-first** for HTML pages — always fresh, fallback to cached version

```javascript
const CACHE_NAME = 'app-v1';
const STATIC_CACHE = 'app-static-v1';
const STATIC_ASSETS = ['/', '/login', '/manifest.json', '/pwa-icons/icon-192.png'];
const CACHEABLE_EXTENSIONS = ['.css', '.js', '.woff2', '.woff', '.ttf'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) => Promise.all(
            names.filter((n) => n !== CACHE_NAME && n !== STATIC_CACHE).map((n) => caches.delete(n))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    if (request.method !== 'GET') return;
    if (url.pathname.startsWith('/api/')) return; // never cache API
    if (isCacheableAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }
    if (url.origin === location.origin) {
        event.respondWith(networkFirst(request));
    }
});

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    try {
        const response = await fetch(request);
        if (response.ok) { const cache = await caches.open(STATIC_CACHE); cache.put(request, response.clone()); }
        return response;
    } catch { return new Response('Offline', { status: 503 }); }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) { const cache = await caches.open(CACHE_NAME); cache.put(request, response.clone()); }
        return response;
    } catch {
        return await caches.match(request) || new Response('Offline', { status: 503 });
    }
}

function isCacheableAsset(pathname) {
    return CACHEABLE_EXTENSIONS.some((ext) => pathname.endsWith(ext));
}
```

**Register in both layouts** (`app.blade.php` and `guest.blade.php`):
```html
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').catch(function() {});
    });
}
</script>
```

**Key design choices:**
- API calls (`/api/*`) are never cached — always network
- POST/PUT/DELETE requests pass through unchanged — never cached
- CDN assets (Font Awesome, Chart.js) use cache-first if they match extensions
- Old caches are cleaned on activate (bump version name when updating)
- `.catch(function() {})` on register prevents console errors in unsupported browsers

**Bumping cache version:** When you change `sw.js`, change `CACHE_NAME` and `STATIC_CACHE` strings (e.g., `app-v1` → `app-v2`). The activate event deletes old caches automatically.

### 6. Push Notification Infrastructure (Without HTTPS)

Push notifications **require HTTPS** — browsers block them on HTTP. But you can build the infrastructure now and enable it later.

**Database:** `push_subscriptions` table (user_id, endpoint, auth_key, p256dh_key, user_agent) + notification preference columns on users table (notify_daily_journal, notify_weekly_review, notify_trade_result booleans; daily_journal_time, weekly_review_day).

**User-facing UI:** Notification settings page with toggle switches for each reminder type + push browser subscribe button. Show HTTPS warning banner when not on secure connection.

**Subscribe flow (JS):**
1. `Notification.requestPermission()` → must be user-gesture triggered
2. `navigator.serviceWorker.ready.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: vapidPublicKey })`
3. Send subscription object to server (`POST /notifications/subscribe`)

**What's needed to actually send pushes (deferred until HTTPS):**
1. Generate VAPID keys: `npx web-push generate-vapid-keys` → add `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY` to `.env`
2. Install `web-push` npm package or use Laravel WebPush package (`composer require minishlink/web-push`)
3. Use VAPID keys in the subscribe response so JS can create subscription
4. To send: use the web-push library with stored subscription + VAPID private key

**Pitfall:** Without VAPID keys, the subscribe flow will fail. The settings page should gracefully handle this and show a message that push will activate after HTTPS + VAPID setup.
