---
name: laravel-fullstack
title: Laravel Full-Stack Development
description: Setup and develop Laravel full-stack projects (Blade, Inertia.js + React, or Vue 3 SPA) with MySQL/SQLite on Ubuntu/Debian servers.
tags: [php, laravel, react, vue, mysql, sqlite, vite, inertia, breeze, blade, fullstack]
---

## Vue 3 SPA with Laravel

For building a Vue 3 + Vue Router SPA (not Inertia) within Laravel, see `references/vue3-spa-laravel.md`.
For Laravel Sanctum authentication with Vue 3 SPA, see `references/laravel-sanctum-vue3-spa.md`.
Key pitfalls: SPA catch-all route must be AFTER API routes; SQLite needs chmod 666/777 under nginx; CSRF meta tag required for Vue POST.

## Dark Theme UI Design

When implementing custom dark themes (NexPanel-style, custom color palettes), **validate WCAG contrast ratios BEFORE deployment**. See `references/dark-theme-contrast-validation.md` for:
- Contrast ratio calculation & WCAG standards (AA/AAA)
- Common pitfalls: small font sizes + low contrast
- Quick fix formulas (brightness, font-weight, size)
- Tool usage warnings (don't use execute_code for file edits)

## Production Migrations

### `php artisan migrate` cancelled in production

Laravel detects the `APP_ENV=production` and blocks `migrate` interactively with "APPLICATION IN PRODUCTION" — the command cancels, no migration runs.

**Fix:** Always use `--force` when running migrations on a live/production server:
```bash
php artisan migrate --force
```

### `migrate:status` checks wrong database

If the project uses multiple database connections (e.g. `mysql` as default + `sqlite` for tests), `migrate:status` may default to the wrong connection, causing confusing "table not found" errors or false "already ran" reports.

**Check first:** Look at `.env` for `DB_CONNECTION=` to know the default, then run:
```bash
php artisan migrate:status --database=mysql
```
Always specify `--database=<connection-name>` explicitly when the project has multiple connections.

## SQLite Deployment Pitfall

When using SQLite with nginx/php-fpm (not artisan serve), both the `.sqlite` file AND its parent `database/` directory need write permissions:
```bash
chmod 666 database/database.sqlite
chmod 777 database/
```
Symptom: "attempt to write a readonly database" on session writes. Artisan serve runs as current user and doesn't hit this.

## Nginx and PHP-FPM Configuration Pitfall

When configuring Nginx to work with PHP-FPM for a Laravel (or general PHP) project, ensure that the `try_files` directive is correctly set to pass requests to `index.php`. A common mistake is to have:

    location / {
        try_files $uri $uri/ =404;
    }

This will return 404 for PHP files that do not correspond to a physical file on disk (like `index.php`). Instead, use:

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

Additionally, verify that the `fastcgi_pass` directive points to the correct PHP-FPM socket (e.g., `unix:/run/php/php8.3-fpm.sock;`) and that the socket file exists and is accessible by the nginx user.

## npm Peer Dependency Conflict (Vite 8.x + Breeze React)

When installing Breeze with React on Laravel 13 (Vite 8.x), you may hit:

```
npm ERR! ERESOLVE unable to resolve dependency tree
npm ERR! Could not resolve dependency:
npm ERR! peer vite@"^4.2.0 || ^5.0.0 || ^6.0.0 || ^7.0.0" from @vitejs/plugin-react@4.7.0
```

**Fix:** Use `--legacy-peer-deps` flag:
```bash
npm install --legacy-peer-deps
```

Do NOT use plain `npm install` — it will fail with Vite 8.x + @vitejs/plugin-react@4.

**Context:** Laravel 13 + Breeze React stack uses Vite 8.x which conflicts with plugin-react's peer dependency range.

## Blank Screen Debugging (Inertia.js + React/Vue)

When debugging blank screens in Inertia.js apps (Laravel + React/Vue), follow this systematic checklist:

### 1. Server-Side Checks
- **Routes**: `grep -r "ControllerName" routes/web.php` — remove stale route references
- **Leftover files**: After `git reset --hard <commit>`, files created after that commit may remain on disk. Manually delete:
  ```bash
  rm -f app/Http/Controllers/StaleController.php
  rm -f app/Models/StaleModel.php
  ```
- **Cache**: Clear all Laravel caches after deletions:
  ```bash
  php artisan optimize:clear
  php artisan route:clear
  composer dump-autoload --no-scripts --optimize
  ```
- **OPcache**: Clear PHP opcache after deleting PHP files:
  ```bash
  php -r "opcache_reset();"
  sudo systemctl restart php8.3-fpm
  ```

### 2. Permission Fixes
Storage/logs permission issues cascade into failures. Fix:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo bash -c "echo '' > storage/logs/laravel.log"
```

### 3. Asset Verification
Check if Vite-built assets load correctly:
```bash
curl -s -o /dev/null -w "%{http_code}" https://domain.com/build/assets/app-XXXX.js
curl -s -o /dev/null -w "%{http_code}" https://domain.com/build/assets/app-XXXX.css
```

### 4. Client-Side Checks
- **Browser Console (F12)**: Check for red JavaScript errors
- **Hard refresh**: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
- **Incognito mode**: Test without browser cache
- **Network tab**: Verify all JS/CSS assets return HTTP 200

### 5. Log Analysis
```bash
tail -50 storage/logs/laravel.log | grep -A 5 "ERROR\|Exception"
```

**Key pitfall**: Blank screen often comes from stale PHP files that survived `git reset --hard`. Always verify with `ls -la app/Http/Controllers/` and `ls -la app/Models/` after resetting.

## Git Reset Hard: Leftover Files Pitfall

`git reset --hard <commit>` does NOT delete files that were created after `<commit>`. They remain on disk and can cause:
- Class not found errors (if you reset before a feature, but the controller still exists)
- Route errors (stale routes pointing to deleted controllers)
- Database errors (models referencing dropped tables)

**Always after `git reset --hard`:**
1. Check for unexpected files: `git status --short`
2. Manually delete new files: `git clean -fd`
3. Verify controllers/models: `ls -la app/Http/Controllers/ app/Models/`
4. Clear caches: `php artisan optimize:clear && composer dump-autoload`

## Tailwind CSS: Arbitrary Values in Custom CSS

**DON'T** use Tailwind arbitrary value syntax (e.g., `text-[9px]`) in custom CSS files — it causes lightningcss minify errors. See `references/tailwind-css-arbitrary-pitfall.md` for:
- Why `.text-[9px]` fails as CSS selector (but works in JSX)
- Build error details and fix options
- Where arbitrary values DO work (@apply, JSX classes)

**Quick fix:** Remove from CSS, use standard selectors or @apply directive instead.

## Tool Choice: File Editing in Laravel Projects

**NEVER** use `execute_code` with `read_file/write_file` for code modifications — it corrupts files by duplicating line numbers.

**Symptom:**
```jsx
     1|     1|import { Link } from '@inertiajs/react';
     2|     2|import { useState } from 'react';
```
→ Build fails: "Unexpected token"

**Why it happens:** The `read_file` tool returns content with line numbers prefixed (`     1|content`). When you write this back, the line numbers become part of the file.

**ALWAYS use `patch` tool for code changes:**
```xml
<invoke name="patch">
  <parameter name="mode">replace

[... rest of SKILL.md unchanged ...]
