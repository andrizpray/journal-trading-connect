# Trading Platform API Integration Reference

## MetaAPI (metaapi.cloud)

Cloud REST + WebSocket API for MetaTrader 4/5. **MetaApi is a paid service** — no permanent free tier exists, though a 7-day trial is available.

### Pricing (as of May 2026)

| Plan | Cost | Trial | Free MT Accounts | Notes |
|---|---|---|---|---|
| **Regular** | $30/month | 7 days (once) | 1 included | Shared API servers |
| **Extended** | $100/month | 7 days (once) | 1 included | Dedicated servers, priority support |
| **Business** | Custom | — | Custom | 250+ accounts, SLA, custom infra |

- API access billed pay-as-you-go on top of subscription cost
- Historical market data API included with all paid plans
- Signup bonus: $5 credit applied automatically

### Connection Types

**`type: 'cloud'` (paid subscription required):** No EA needed. Provide broker credentials (login, password/investor password, server name). MetaAPI runs MT terminal in their cloud. Account auto-detects broker settings. Set `reliability: 'high'` for production.

**End-user configuration:** Create account in DRAFT state (no login/password), generate a link for user to enter credentials themselves (`create_configuration_link(ttl_in_days: 7)`). Good for privacy — your server never sees broker credentials.

**Account access token:** Narrow-down API token per-account for minimal exposure:
```python
account_access_token = await api.token_management_api.narrow_down_token({
    'applications': ['metaapi-rest-api', 'metaapi-rpc-api', 'metastats-api'],
    'roles': ['reader'],
    'resources': [{'entity': 'account', 'id': account_id}]
}, validity_in_hours=24)
```

### Investor Password (Read-Only)

For trading journals, use investor password only — view trades/positions/account but cannot execute. Much safer than master password. Supported by MetaAPI's `password` field on account creation.

### RPC API — Read Trade History

For monitoring/journal apps, use RPC API (simpler than streaming):

```python
connection = account.get_rpc_connection()
await connection.connect()
await connection.wait_synchronized()

# History by time range
orders = await connection.get_history_orders_by_time_range(start_time=start, end_time=end)
deals = await connection.get_deals_by_time_range(start_time=start, end_time=end)

# History by ticket or position ID
orders = await connection.get_history_orders_by_ticket(ticket='1234567')
deals = await connection.get_deals_by_position(position_id='1234567')
```

### Streaming API — Real-Time

WebSocket for real-time position, price, and account updates. Better for live dashboards.

### MetaStats API

Cloud trading statistics REST API — calculate stats like Myfxbook/Metrix. Separate from core MetaAPI.

### Key REST Endpoints (mt-providers-api.metaapi.cloud/v2)

```
GET    /users/current/accounts                    — list accounts
POST   /users/current/accounts                    — create account
GET    /users/current/accounts/{id}                — get account
PATCH  /users/current/accounts/{id}                — update account
DELETE /users/current/accounts/{id}                — remove account
POST   /users/current/accounts/{id}/deploy         — start API server
POST   /users/current/accounts/{id}/undeploy       — stop API server
POST   /users/current/accounts/{id}/redeploy       — restart API server
```

### Account Creation Errors

- `E_SRV_NOT_FOUND` — server file not found; check server name or use provisioning profile
- `E_AUTH` — authentication failed; check login/password
- `E_SERVER_TIMEZONE` — broker settings detection failed; retry or use provisioning profile

### Supported Brokers

Nearly all MT4/MT5 brokers: ICMarkets, Exness, FBS, XM, FXTM, RoboForex, Alpari, InstaForex, Pepperstone, OctaFX, etc.

### Limitations

- **Only MT4/MT5** — no cTrader, TradingView, or non-MT brokers
- **No permanent free tier** — only 7-day trial (activatable once) + 1 free MT account per subscription
- **Cloud accounts without subscription** — not possible; minimum $30/month
- Custom EAs only supported on g1 infrastructure, MT4 accounts only

### SDKs Available

- Node.js: `npm install metaapi.cloud-sdk`
- Python: `pip install metaapi-cloud-sdk`
- .NET, Java also available
- GitHub: `metaapi/metaapi-python-sdk`, `metaapi/metaapi-javascript-sdk`

### Integration Pattern for Laravel Trading Journal

1. User inputs broker login + investor password + server name in app
2. Server sends credentials to MetaAPI via REST API (create account with `type: 'cloud'`)
3. Deploy the account via API (`POST /accounts/{id}/deploy`)
4. Store only the MetaAPI account ID (NOT the broker password)
5. Poll trade history periodically (every 5-15 min) via RPC `get_history_orders_by_time_range`
6. Compare with existing DB records, import only new trades (dedup by deal ID)
7. Use WebSocket streaming for real-time position monitoring (optional)

### Credentials Needed to Start

- Sign up at https://app.metaapi.cloud/token → get API token
- Trial: 7 days free, activatable once, 1 MT account included
- Production: minimum $30/month Regular subscription
- No truly free option — see EA Logger below for a free alternative

---

## EA Logger — Free Alternative (No Third-Party API)

Custom MQL indicator that sends trade history from MT4/MT5 terminal directly to your Laravel API via HTTP POST. **100% free, no subscriptions, no third-party services.**

### Architecture

```
MT4/MT5 Terminal (EA Logger indicator running here)
    ↓ HTTP POST every 30 seconds (OnTimer)
    ↓ Bearer token auth + JSON body
    ↓
Laravel API (POST /api/ea/trade/batch)
    ↓ validate token → dedup by ticket → save to trade_histories
    ↓
Dashboard / Analytics / Journal (auto-updated)
```

### Why an Indicator (not Expert Advisor)?

Indicators are lighter weight, don't interfere with user's existing EA trading strategies, and don't require separate chart attachment. They run in the background via `OnTimer()`.

### EA Logger Design Principles

1. **Non-blocking:** `OnTimer()` runs in background, never calls `Sleep()`. Trade execution is never delayed.
2. **Batch sending:** Collects up to 50 trades per request. Checks `OrdersHistoryTotal()` for changes.
3. **Deduplication by ticket:** Server checks `ticket + trading_account_id` before inserting. EA can safely re-send all history.
4. **Offline fallback:** Writes to local CSV file (`MQL4/Files/jtc_log.csv`) if HTTP request fails. Can re-sync when connection returns.
5. **Minimal data per request:** ~1KB per trade JSON. ~50KB max per batch request. ~300ms HTTP round-trip.
6. **Input parameters user configures:** Server URL, API Token, send interval (default 30s), max trades per request (default 50), debug mode.

### API Endpoints (Laravel side)

Separate `routes/api.php` with `ea.token` middleware (validates `Authorization: Bearer {token}` against `trading_accounts.api_token`):

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/ea/trade` | Single trade |
| POST | `/api/ea/trade/batch` | Batch trades (max 50) |
| POST | `/api/ea/heartbeat` | EA alive signal, returns server_time + pending count |
| GET | `/api/ea/config` | Returns interval, max_trades, server config |

**Auth:** API token stored in `trading_accounts.api_token` column. Auto-generated on account creation via `booted()` creating event with `bin2hex(random_bytes(32))`.

**Key difference from web routes:** API routes don't use sessions/CSRF. EA can't provide cookies. The `routes/api.php` in Laravel 11 must be explicitly registered in `bootstrap/app.php` via `->withRouting(api: __DIR__.'/routes/api.php')`.

### MT5 Pitfalls (Discovered via User Testing)

**MT5 deal pairing requires wider scan range.** To find the entry deal for a closed position, scan from `totalDeals - (InpMaxTrades * 3)`, not just `totalDeals - InpMaxTrades`. Reason: there are both entry AND exit deals per position, so you need roughly 2× the visible range to reliably find paired entries.

**Handle `DEAL_ENTRY_INOUT` (partial closes).** MT5 can partially close positions, producing deals with entry type `DEAL_ENTRY_INOUT` (value 2) alongside `DEAL_ENTRY_OUT` (value 1). Always check for both when filtering closed trades:
```mql5
if(dealEntry != DEAL_ENTRY_OUT && dealEntry != DEAL_ENTRY_INOUT) continue;
```

**Skip zero-volume deals.** MT5 generates internal bookkeeping deals (balance, credit) that have volume 0. Always check `if(volume == 0) continue;` before processing a deal.

**Deal type for closed trades:** In MT5, a closed BUY position has a closing SELL deal, and vice versa. The deal type reflects the closing direction. For trade classification (buy vs sell), track the opening deal's type via position ID matching, not the closing deal's type.

### MQL4 vs MQL5 Differences

Only the history-reading functions differ. All HTTP/sync logic is identical.

| MQL4 | MQL5 |
|---|---|
| `OrdersHistoryTotal()` | `HistorySelect(0, TimeCurrent())` + `HistoryDealsTotal()` |
| `OrderSelect(i, SELECT_BY_POS, MODE_HISTORY)` | `HistoryDealSelect(ticket)` |
| `OrderTicket()` | `HistoryDealGetInteger(ticket, DEAL_TICKET)` |
| `OrderSymbol()` | `HistoryDealGetString(ticket, DEAL_SYMBOL)` |
| `OrderType()` → `OP_BUY`/`OP_SELL` | `DEAL_TYPE_BUY`/`DEAL_TYPE_SELL` (different enum) |
| `OrderProfit()` | `HistoryDealGetDouble(ticket, DEAL_PROFIT)` |
| `MODE_DIGITS` | `SymbolInfoInteger(symbol, SYMBOL_DIGITS)` |

### User Setup Flow (3 steps)

1. **Web:** User logs in → Connect → EA Logger → selects trading account → copies API token → downloads `.mq4`/`.mq5` file
2. **Terminal:** User copies EA file to `MQL4/Indicators` or `MQL5/Indicators` folder → compiles in MetaEditor → drags indicator onto chart
3. **Configure:** User pastes API token into indicator input parameter → enables `Allow WebRequest` in MT Tools > Options > Expert Advisors (must whitelist server URL)

### Important MT4/MT5 Requirements

- **`WebRequest()` must be enabled:** Tools > Options > Expert Advisors > "Allow WebRequest" → add server URL to allowed list. Without this, all HTTP requests silently fail.
- **`WebRequest()` has a 5-second timeout** built into MT4. If server doesn't respond in 5s, returns -1. Check `GetLastError()` for diagnostics.
- **EA cannot be compiled on the server** — user must compile in their own MetaEditor, or you provide pre-compiled `.ex4`/`.ex5` files.
- **Files written by EA** go to `TerminalInfoString(TERMINAL_DATA_PATH)/MQL4/Files/` or `MQL5/Files/`. Need `FILE_SHARE_READ` flag for concurrent access.

### When to Use EA Logger vs MetaAPI vs CSV Import

| Criteria | CSV Import | EA Logger | MetaAPI |
|---|---|---|---|
| **Cost** | Free | Free | $30+/month |
| **Real-time** | No (manual) | Near real-time (30s delay) | Real-time |
| **Terminal required** | No (export file) | Yes (must be running) | No (cloud-hosted) |
| **Setup complexity** | Low | Medium | High |
| **Third-party dependency** | None | None | MetaAPI service |
| **Best for** | Historical bulk import | Active traders with always-on terminal | Production apps needing guaranteed uptime |

### Plan Reference

Full implementation plan (14 tasks across 4 phases) saved at `docs/plans/ea-logger-integration.md` in the journal-trading-connect project.

---

## PHP Implementation Patterns

### Middleware (`EaTokenAuth.php`)

Key design choices:
- Returns JSON errors (401) — EA parses JSON responses
- Stores account on `$request->attributes` (not auth user) — this is a machine-to-machine pattern, not a user session
- Checks both token validity AND `is_active` flag

### Controller Response Format

All endpoints return consistent JSON:
```json
// Success
{ "status": "ok", "message": "...", "trade_id": 123, "duplicate": false }

// Duplicate (not an error — 200 OK, just informational)
{ "status": "ok", "message": "Trade already exists", "trade_id": null, "duplicate": true }

// Batch result
{ "status": "ok", "message": "Processed 50 trades", "saved": 45, "duplicates": 5, "errors": 0 }

// Heartbeat
{ "status": "ok", "server_time": "2026-05-03 20:30:00", "pending_trades": 823, "message": "Heartbeat received" }

// Config
{ "status": "ok", "account_id": 5, "server_url": "http://...", "send_interval_seconds": 30, "max_trades_per_request": 50 }

// Auth error (401)
{ "error": "Invalid token", "message": "Token tidak valid atau akun tidak aktif" }
```

**Design choice:** Duplicates return 200 (not 409) because EA re-sends entire history each interval. A 409 would cause unnecessary error logging in the EA.

### Auto-Calculate Logic (Controller)

The controller computes derived fields so EA doesn't have to:
```php
// Result from P&L
private function calculateResult(float $profitLoss): string
{
    if ($profitLoss > 0) return 'win';
    if ($profitLoss < 0) return 'loss';
    return 'break_even';
}

// Duration from dates (fallback if EA doesn't send it)
$duration = $validated['duration_minutes'] ?? null;
if ($duration === null && !empty($validated['close_date'])) {
    $duration = Carbon::parse($validated['open_date'])
        ->diffInMinutes(Carbon::parse($validated['close_date']));
}
```

### Account Stats Update

Every successful API call updates the parent `trading_accounts` record:
```php
$account->update([
    'last_synced_at' => now(),
    'total_trades'   => TradeHistory::where('trading_account_id', $account->id)->count(),
    'total_pnl'      => TradeHistory::where('trading_account_id', $account->id)->sum('profit_loss'),
]);
```

This keeps the account summary always current without a separate cron job.

### Batch Validation

Laravel's `validate()` with `trades.*.field` syntax handles array-of-objects validation. Max 50 enforced by both validation (`max:50`) and EA config response. Each trade in the batch is wrapped in try/catch so one bad trade doesn't fail the entire batch:
```php
foreach ($validated['trades'] as $tradeData) {
    try {
        // dedup + insert
        $saved++;
    } catch (\Exception $e) {
        $errors++;
    }
}
```

### CORS Configuration

API routes used by EA need `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_methods' => ['GET', 'POST'],
'allowed_origins' => ['*'],  // EA can connect from anywhere
'allowed_headers' => ['Content-Type', 'Authorization'],
'supports_credentials' => false,
```

This is safe because actual auth is via token, not origin-based. Without a valid token, all requests return 401 regardless of origin.
