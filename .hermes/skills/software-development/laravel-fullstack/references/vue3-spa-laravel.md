# Vue 3 SPA with Laravel — Setup & Pitfalls

Pattern for building a Vue 3 + Vue Router SPA within a Laravel project (NOT Inertia — raw Vue SPA served by a single Blade view, API routes for data).

## Setup Steps

### 1. Install Dependencies
```bash
npm install vue@3 vue-router@4 @vitejs/plugin-vue
npm install -D tailwindcss @tailwindcss/vite
```

**Pitfall**: `npm install -D` with Tailwind/Vite may be detected as a long-running process by the terminal tool. Use background execution:
```python
terminal("cd project && npm install -D tailwindcss @tailwindcss/vite 2>&1; echo DONE:$?", background=True)
```

### 2. Configure vite.config.js
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': '/resources/js' },
    },
});
```

### 3. Create Vue App Entry Point
`resources/js/app.js`:
```js
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import HomePage from './pages/HomePage.vue';

const routes = [
    { path: '/', component: HomePage },
    // Add more routes
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const app = createApp(App);
app.use(router);
app.mount('#app');
```

### 4. Create Single Blade View
`resources/views/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>App Name</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
```

**Critical**: `<meta name="csrf-token">` must be in `<head>` for all `fetch()` POST requests from Vue components.

### 5. Route Setup — SPA Catch-All (ORDER MATTERS!)
`routes/web.php`:
```php
use App\Http\Controllers\Api\ProductController;
// ... other API controllers

// API routes MUST be registered BEFORE the catch-all
Route::prefix('api')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    // ... other API routes
});

// SPA catch-all — MUST be last
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
```

**Pitfall**: If the catch-all `/{any}` route is registered before `/api/*` routes, ALL API requests return the SPA HTML instead of JSON. No error, just wrong response. Always register API routes first.

### 6. Directory Structure
```
resources/js/
├── app.js          # Entry point (createApp + router)
├── App.vue         # Root component (<router-view />)
├── pages/          # Route-level components
│   └── HomePage.vue
├── components/     # Reusable components
│   └── ProductCard.vue
└── stores/         # Reactive state (no Pinia needed for simple apps)
    └── cart.js     # Use reactive() + computed() from Vue
```

### 7. Simple State Store (No Pinia)
`resources/js/stores/cart.js`:
```js
import { reactive, computed } from 'vue';

const state = reactive({ items: [] });

export function useCart() {
    const itemCount = computed(() => state.items.length);
    function addItem(product) { state.items.push(product); }
    function clearCart() { state.items = []; }
    return { items: computed(() => state.items), itemCount, addItem, clearCart };
}
```

This composable pattern works well for POS-style apps. No need for Pinia unless you need devtools or plugins.

## Critical Pitfalls

### ❌ SPA Catch-All Swallows API Routes
If `Route::get('/{any}', ...)` is before `Route::prefix('api', ...)`, all API calls return HTML. **Always register API routes before the catch-all.**

### ❌ SQLite Readonly Under Nginx/PHP-FPM
When deploying with SQLite and nginx, the `database.sqlite` file AND its parent directory need write permissions:
```bash
chmod 666 database/database.sqlite
chmod 777 database/
```
Without this, sessions fail to write → "attempt to write a readonly database" error. Artisan serve doesn't have this issue (runs as current user).

### ❌ `npm install -D` Detected as Long-Running Process
The terminal tool may flag `npm install -D tailwindcss @tailwindcss/vite` as a foreground server process. Workaround: run in background with `background=true` or append `; echo DONE:$?` to force completion signal.

### ❌ Missing CSRF Token for API POST from Vue
Vue `fetch()` POST requests need the CSRF token from `<meta name="csrf-token">`. Without it, returns 419. Include in headers:
```js
headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
}
```

### ❌ Order Model Auto-Generate Missing Fields
When creating orders via API, use `DB::transaction()` and auto-calculate subtotal/tax/grand_total server-side. Never trust client-side totals. Generate `order_number` in model `booted()` creating hook.
