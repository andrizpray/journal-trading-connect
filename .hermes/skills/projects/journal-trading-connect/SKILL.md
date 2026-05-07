---
name: journal-trading-connect
title: "Journal Trading Connect (JTC)"
description: "Development conventions, pitfalls, and patterns for the Journal Trading Connect Laravel project — trading journal with EA Logger integration for MT4/MT5."
tags: [laravel, blade, tailwind, project, trading, ea-logger]
---

# Journal Trading Connect (JTC)

Project: `/home/ubuntu/journal-trading-connect` — Laravel 13 + Blade + Tailwind CSS (dark theme).
Domain: `https://eatrade-journal.site` — SSL active (Let's Encrypt, auto-renew).
DB: `trading_connect` on 127.0.0.1:3306.
GitHub: main branch, push after every update batch.

## Stack & Config

- **Tailwind CSS** dark theme (custom CSS variables: `--bg-primary`, `--bg-secondary`, `--text-primary`, `--text-secondary`)
- **Font Awesome** for icons
- **Chart.js** for dashboard charts
- **Maatwebsite Excel** for import/export
- **SMTP**: SumoPod (`smtp.sumopod.com:465`, SSL, any sender OK), `MAIL_FROM_ADDRESS=noreply@eatrade-journal.site`
- Nginx on port 8081 (proxied to domain via SSL)
- Email verification: `MustVerifyEmail` on User model, dashboard middleware `verified`

## Theme Conventions

- **Dark theme only** — no light mode toggle
- Card backgrounds: `bg-gray-800` / `bg-gray-900`
- Text colors: `text-white` (headings), `text-gray-400` (secondary)
- Accent colors: `text-cyan-400` (tokens, code), `text-emerald-400` (success), `text-red-400` (error)
- Buttons: `bg-cyan-600 hover:bg-cyan-500` (primary), `bg-emerald-600` (success state)

## Critical Pitfalls

### ❌ `@section('scripts')` vs `@push('scripts')` — Silent JS Failure

Layout `app.blade.php` uses `@stack('scripts')`. Child views MUST use `@push('scripts')` not `@section('scripts')`.

**Symptom:** JS functions undefined, AJAX buttons do nothing, no errors in console — the `<script>` block simply never renders.

**Check:** `grep -n '@yield\|@stack' resources/views/layouts/app.blade.php`

### ❌ Token display trailing whitespace

Blade `{{ $token }}` inside a div with line breaks adds whitespace. Always use `data-token` attribute for JS-retrievable values. See `laravel-fullstack` skill → `references/blade-pitfalls.md` for full pattern.

### ❌ Copy button `event` not passed

`onclick="copyToken()"` — `event` is NOT available inside the function. Must use `onclick="copyToken(event)"` and declare `function copyToken(event) { ... }`.

### ❌ Duplicate flash notifications

Layout has a toast system that auto-shows `session('success')`/`session('error')`. Do NOT add inline flash messages in child views — causes duplicates.

### ❌ CSRF on AJAX POST

AJAX POST requests need CSRF token from meta tag:
```js
headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```
Without it, returns 419 "Page Expired". Curl tests will always fail with 419 — this is expected, only browser with session works.

## EA Logger Integration

See `laravel-fullstack` skill → `references/laravel-ea-logger-api.md` for full API docs.

### Connection Test — 4 States

The "Test Koneksi" button checks real EA activity via AJAX:

| Status | Condition | UI Color |
|--------|-----------|----------|
| **connected** | Server OK + `last_synced_at` < 10 min ago | 🟢 Emerald |
| **disconnected** | Server OK + `last_synced_at` > 10 min ago | 🟡 Yellow |
| **not_setup** | Server OK + never synced | 🔵 Blue |
| **server_error** | Cannot reach server | 🔴 Red |

**Flow:** `ConnectController@testConnectionAjax` → `performConnectionTest()` → self-ping `GET /api/ea/ping` → check `last_synced_at` + `TradeHistory` count.

**Token resolution (3 cases):**
1. **Session token exists + matches account** → Authenticated ping (full verification, Bearer token in header) → `tokenVerified = true`
2. **Session token empty, `api_token_hash` EXISTS in DB** → Unauthenticated reachability check (HTTP 200/401/403/404/429 = server up; connection error = server down) → `tokenVerified = false` (token not re-verified but assumed valid since hash exists)
3. **Session token empty, `api_token_hash` NOT in DB** → Return `token_unavailable` (token never generated)

**⚠️ Pitfall — Session-only token bug (pre-fix e7e2973):**
`performConnectionTest()` originally only read from session (`$request->session()->get('new_token')`). After session expire/close, even if `api_token_hash` existed in DB, it returned `token_unavailable`. The fix adds DB fallback (case 2 above). If editing this method, ALWAYS maintain the DB hash fallback — never assume session token is available.

### API Endpoints

All under `routes/api.php` with `ea.token` middleware (Bearer token auth):
- `GET /api/ea/ping` — connectivity test (no side effects)
- `POST /api/ea/trade` — single trade
- `POST /api/ea/trade/batch` — batch trades (max 50)
- `POST /api/ea/heartbeat` — EA alive signal (updates `last_synced_at`)
- `GET /api/ea/config` — EA initial config

### EA Files

- `public/ea/JTC_Logger.mq4` — MT4 EA file
- `public/ea/JTC_Logger.mq5` — MT5 EA file (v1.3)
- Default server URL: `https://eatrade-journal.site` (HTTPS, not IP:port)
- EA uses `StripTrailingSlash()` on URL, `SendRequest()` with method param (GET for ping, POST for trades)
- **MQL5 WebRequest pitfalls** — see `laravel-fullstack` skill → `references/mql5-webrequest-pitfalls.md` for full list (null terminator, headers \r\n, trade_type logic, error codes)

## Data Model

### TradingAccount
- Fields: `id`, `account_number`, `broker`, `platform`, `api_token` (nullable, legacy plain-text), `api_token_hash` (64-char SHA-256, non-null after first token generation), `is_active`, `last_synced_at`, `total_trades`, `total_pnl`, `user_id`
- **Token design:** Plain token returned ONCE at generate/regenerate time and stored only in user session. DB stores only the SHA-256 hash. Plain token cannot be retrieved again after session ends — this is intentional (security).
- Auto-generates `api_token_hash` on creation via boot method (calls `bin2hex(random_bytes(32))`, stores hash)
- `regenerateToken()` clears old hash, generates new plain token, stores new hash
- `matchesApiToken(string $plainToken)` — verifies plain token against stored hash using `hash_equals()`
- Belongs to User, has many TradeHistory
- **⚠️ Pitfall:** Never try to "decrypt" or retrieve the plain token from `api_token_hash` — only `regenerateToken()` can produce a new valid token. The EA must store the plain token locally after generation.

### TradeHistory
- Fields: `user_id`, `trading_account_id`, `ticket`, `open_date`, `close_date`, `currency_pair`, `trade_type`, `lot_size`, `open_price`, `close_price`, `stop_loss`, `take_profit`, `swap`, `commission`, `profit_loss`, `result`, `duration_minutes`, `comment`, `imported_at`
- `result`: calculated from `profit_loss` — 'win' / 'loss' / 'break_even'
- Duplicate prevention: unique on `(ticket, trading_account_id)`

## Key Files

- `app/Http/Controllers/ConnectController.php` — EA Logger setup page, token management, connection test (4 states), Telegram setup
- `app/Http/Controllers/EaApiController.php` — API endpoints for EA (ping, trade, batch, heartbeat, config)
- `app/Http/Middleware/EaTokenAuth.php` — Bearer token validation, injects `ea_account` into request
- `app/Models/TradingAccount.php` — auto token generation, `regenerateToken()`
- `resources/views/connect/ea-logger-setup.blade.php` — Setup wizard (3 steps: select account, token, download EA) + connection test
- `resources/views/layouts/app.blade.php` — Dark theme, `@stack('scripts')`, toast system, CSRF meta tag

## User Preferences

- Dark theme only
- Commit + push to GitHub after every update batch
- Concise responses, consolidate into one message
- "Jangan dipaksakan" — quality over speed, verify each step
