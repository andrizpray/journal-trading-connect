# EA Logger API Integration

Pattern for connecting MT4/MT5 Expert Advisors to Laravel backend for real-time trade sync.

## Architecture Overview

```
MT4/MT5 Terminal → EA Logger (MQ4/MQ5) → HTTPS POST → Laravel API → MySQL
                                      ↑
                              Bearer Token Auth
```

**Key Components:**
- `app/Http/Middleware/EaTokenAuth.php` — validates Bearer token against `trading_accounts.api_token`
- `app/Http/Controllers/EaApiController.php` — handles `/api/ea/trade`, `/api/ea/trade/batch`, `/api/ea/heartbeat`, `/api/ea/config`
- `public/ea/JTC_Logger.mq4` and `.mq5` — EA files downloadable by users

## API Endpoints

All under `Route::middleware('ea.token')`:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/ea/ping` | GET | Lightweight connectivity + token check, returns account info |
| `/api/ea/trade` | POST | Single trade from EA |
| `/api/ea/trade/batch` | POST | Batch trades (max 50) |
| `/api/ea/heartbeat` | POST | EA alive signal, updates `last_synced_at` |
| `/api/ea/config` | GET | EA requests initial config |

### `/api/ea/ping` — Connection Test Endpoint

Purpose: lightweight endpoint for verifying server reachability AND token validity without side effects (heartbeat updates `last_synced_at`, ping does not).

Response:
```json
{
    "status": "ok",
    "message": "Connection successful",
    "account_number": "24996648",
    "broker": "Vantage",
    "platform": "MT5",
    "server_time": "2026-05-04 21:30:00"
}
```

**Rule:** Always use `GET /api/ea/ping` for connection test UI — never `POST /api/ea/heartbeat` (heartbeat has side effects and is for EA-initiated keepalive only).

## Authentication Flow

1. User generates `api_token` on TradingAccount (auto-created on model creation)
2. User downloads EA, attaches to chart
3. User pastes token into EA input parameter `InpApiToken`
4. EA sends `Authorization: Bearer {token}` with each request
5. `EaTokenAuth` middleware validates token, injects `$account` into request

## Connection Status Indicator

In views, show EA connection status based on `last_synced_at`:

```blade
@php
    $isConnected = $account->last_synced_at && $account->last_synced_at->gt(now()->subMinutes(10));
@endphp

@if($isConnected)
    <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-emerald-400">EA Terhubung</span>
    </span>
@elseif($account->last_synced_at)
    <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
        <span class="text-yellow-400">EA Tidak Aktif</span>
    </span>
@else
    <span class="flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-gray-500"></span>
        <span class="text-gray-400">Belum Pernah Terhubung</span>
    </span>
@endif
```

## Test Connection Pattern

**AJAX approach (preferred):**

Controller:
```php
public function testConnectionAjax(Request $request)
{
    $account = TradingAccount::where('user_id', Auth::id())
        ->findOrFail($request->account_id);

    $result = $this->performConnectionTest($account);
    return response()->json($result);
}

private function performConnectionTest(TradingAccount $account): array
{
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $account->api_token,
            'Accept' => 'application/json',
        ])->withoutVerifying()
          ->timeout(10)
          ->get(config('app.url') . '/api/ea/ping');

        if ($response->successful()) {
            $apiOk = true;
        } else {
            $status = $response->status();
            if ($status === 401) {
                $apiMessage = 'Token tidak valid';
            } else {
                $apiMessage = "Server merespon HTTP {$status}";
            }
        }
    } catch (\Exception $e) {
        $apiMessage = 'Server tidak bisa dijangkau';
    }

    // Step 2: Check EA activity based on last_synced_at
    $eaActive = false;
    $lastSync = $account->last_synced_at;
    $totalTrades = TradeHistory::where('trading_account_id', $account->id)->count();

    if ($lastSync) {
        $minutesAgo = $lastSync->diffInMinutes(now());
        $eaActive = $minutesAgo <= 10;
    }

    // 4 connection states
    if ($apiOk && $eaActive) {
        return ['status' => 'connected', ...];
    }
    if ($apiOk && $lastSync && !$eaActive) {
        return ['status' => 'disconnected', ...];
    }
    if ($apiOk && !$lastSync) {
        return ['status' => 'not_setup', ...];
    }
    return ['status' => 'server_error', ...];
}
```

View JS:
```js
function testConnection(accountId) {
    const btn = document.getElementById('testBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    
    fetch('/connect/ea-logger/test-ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ account_id: accountId })
    })
    .then(r => r.json())
    .then(data => {
        // Show inline result, toast notification
        showToast(data.message, data.type);
    });
}
```

## EA File Setup Instructions

User must add server URL to MT4/MT5 WebRequest whitelist:
1. Tools → Options → Expert Advisors
2. Check "Allow WebRequest for listed URL"
3. Add: `https://your-domain.com`
4. OK

**Default EA URL:** Should use HTTPS domain, not IP:port. Update `InpServerUrl` in both `.mq4` and `.mq5` files.

## Duplicate Prevention

EA sends `ticket` (unique trade ID from broker). Controller checks:
```php
$exists = TradeHistory::where('ticket', $validated['ticket'])
    ->where('trading_account_id', $account->id)
    ->exists();

if ($exists) {
    return response()->json(['status' => 'ok', 'duplicate' => true]);
}
```
