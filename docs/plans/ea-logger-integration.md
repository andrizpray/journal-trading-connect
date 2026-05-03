# EA Logger — Auto-sync dari MT4/MT5 ke Server

> **For Hermes:** Gunakan untuk implementasi bertahap.

**Goal:** EA berjalan di terminal MT4/MT5 user → kirim data trade → API endpoint Laravel → simpan ke DB → muncul di dashboard.

**Arsitektur:**
```
MT4/MT5 Terminal (EA Logger berjalan di sini)
    ↓ HTTP POST (setiap ada trade close, polling 30 detik)
    ↓ Bearer token auth + JSON body
    ↓
Server Kita (43.134.37.14:8081/api/ea/trade)
    ↓ validasi token, simpan ke trade_histories
    ↓
Dashboard / Analytics / Journal (otomatis update)
```

**Tech Stack:** Laravel 11 (REST API), MQL4 (EA), MQL5 (EA), Token auth

---

## 📋 REKAP TAHAPAN

| Tahap | Nama | Task | Deliverable |
|-------|------|------|-------------|
| **A** | **API Server** | A1–A6 | Endpoint + Auth + CORS + Route API |
| **B** | **EA Logger MQL4** | B1–B5 | Script EA untuk MT4 (.mq4) |
| **C** | **EA Logger MQL5** | C1 | Adaptasi EA untuk MT5 (.mq5) |
| **D** | **UI & Panduan** | D1–D2 | Halaman setup EA, panduan user |

**Total: 14 task**

---

## Tahap A — API Server (Laravel)

### A1: Tambah kolom `api_token` ke tabel `trading_accounts`

**Objective:** Setiap akun trading punya token unik untuk auth dari EA.

**Files:**
- Create: `database/migrations/xxxx_add_api_token_to_trading_accounts_table.php`

**Langkah:**
1. Buat migration:
```php
Schema::table('trading_accounts', function (Blueprint $table) {
    $table->string('api_token', 64)->nullable()->unique()->after('sync_method');
});
```

### A2: Update model & auto-generate token

**Objective:** TradingAccount auto-generate API token saat create.

**Files:**
- Modify: `app/Models/TradingAccount.php` — tambah `api_token` ke $fillable, booted() auto-generate

```php
protected $fillable = [
    // ...existing fields...
    'api_token',
    'sync_method', // csv, ea, metaapi
];

protected static function booted(): void
{
    static::creating(function ($account) {
        if (empty($account->api_token)) {
            $account->api_token = bin2hex(random_bytes(32));
        }
    });
}
```

### A3: Buat `routes/api.php`

**Objective:** Endpoint API khusus untuk EA, terpisah dari web routes.

**Files:**
- Create: `routes/api.php`
- Modify: `bootstrap/app.php` — register api routes

```php
// routes/api.php
use App\Http\Controllers\EaApiController;
use Illuminate\Support\Facades\Route;

Route::post('/ea/trade', [EaApiController::class, 'storeTrade']);
Route::post('/ea/trade/batch', [EaApiController::class, 'storeBatch']);
Route::post('/ea/heartbeat', [EaApiController::class, 'heartbeat']);
Route::get('/ea/config', [EaApiController::class, 'getConfig']);
```

> **Catatan:** Laravel 11 tidak otomatis load `routes/api.php`. Harus register di `bootstrap/app.php`:
```php
->withRouting(
    web: __DIR__.'/routes/web.php',
    api: __DIR__.'/routes/api.php', // <-- tambah ini
    commands: __DIR__.'/routes/console.php',
    health: '/up',
)
```

**Perbedaan web vs api route:**
- API routes: prefix `/api`, tidak pakai session/CSRF
- Web routes: prefix `/`, pakai session/CSRF/cookies
- EA butuh API routes karena tidak punya session

### A4: Buat middleware `EaTokenAuth`

**Objective:** Validasi request dari EA menggunakan api_token di header.

**Files:**
- Create: `app/Http/Middleware/EaTokenAuth.php`
- Modify: `bootstrap/app.php` — register middleware alias `ea.token`

**Logika:**
1. Ambil `Authorization: Bearer {token}` dari header
2. Cari `TradingAccount` dengan token tersebut
3. Kalau valid → simpan account ke `$request->attributes` (bukan auth user)
4. Kalau invalid → return 401 JSON

```php
class EaTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token required'], 401);
        }

        $account = TradingAccount::where('api_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $request->attributes->set('ea_account', $account);

        return $next($request);
    }
}
```

### A5: Buat `EaApiController`

**Objective:** Handle request POST dari EA — simpan trade history.

**Files:**
- Create: `app/Http/Controllers/EaApiController.php`

**Endpoint:**

**POST `/api/ea/trade`** — Satu trade
```json
// Request body:
{
    "ticket": "123456789",
    "open_date": "2026-05-03 10:30:00",
    "close_date": "2026-05-03 14:15:00",
    "currency_pair": "EURUSD",
    "trade_type": "buy",
    "lot_size": 0.1,
    "open_price": 1.08500,
    "close_price": 1.08750,
    "stop_loss": 1.08300,
    "take_profit": 1.08900,
    "swap": -0.50,
    "commission": -1.00,
    "profit_loss": 25.00,
    "duration_minutes": 225,
    "comment": ""
}

// Response (200):
{
    "status": "ok",
    "message": "Trade saved",
    "trade_id": 123,
    "duplicate": false
}
```

**POST `/api/ea/trade/batch`** — Multiple trades (max 50 per request)
```json
// Request body:
{
    "trades": [
        { "ticket": "123", ... },
        { "ticket": "124", ... }
    ]
}

// Response (200):
{
    "status": "ok",
    "saved": 45,
    "duplicates": 5,
    "errors": 0
}
```

**POST `/api/ea/heartbeat`** — EA kirim sinyal masih hidup
```json
// Response (200):
{
    "status": "ok",
    "server_time": "2026-05-03 20:30:00",
    "pending_trades": 3
}
```

**GET `/api/ea/config`** — EA minta konfigurasi awal
```json
// Response (200):
{
    "account_id": 5,
    "account_number": "12345678",
    "server_url": "http://43.134.37.14:8081",
    "send_interval_seconds": 30,
    "max_trades_per_request": 50
}
```

**Logika `storeTrade`:**
1. Validasi semua field wajib
2. Cek duplicate berdasarkan `ticket` + `trading_account_id`
3. Hitung `result`: profit_loss > 0 → win, < 0 → loss, = 0 → break_even
4. Simpan ke `trade_histories`
5. Update `last_synced_at` di `trading_accounts`
6. Return JSON response

### A6: Konfigurasi CORS untuk API

**Objective:** Supaya EA bisa kirim POST ke server kita.

**Files:**
- Modify: `config/cors.php` (buat file baru)
- Modify: `bootstrap/app.php` — pastikan CORS middleware aktif di API routes

**Catatan:** Di Laravel 11, CORS sudah built-in via `HandleCors` middleware. Tinggal pastikan config ada:
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST', 'GET'],
    'allowed_origins' => ['*'],  // EA bisa dari mana saja
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

> ⚠️ `allowed_origins: '*'` aman untuk EA karena hanya menerima request dengan valid token. Tapi kalau nanti mau restrict, bisa diubah ke domain spesifik.

---

## Tahap B — EA Logger MQL4

### B1: Struktur dasar EA

**Objective:** Template EA yang bisa di-compile di MetaEditor 4.

**Files:**
- Create: `resources/ea/JTC_Logger.mq4`

**Struktur:**
```mql4
//+------------------------------------------------------------------+
//|                                           JTC_Logger.mq4        |
//|                        Journal Trading Connect - EA Logger      |
//|                                     https://github.com/andrizpray |
//+------------------------------------------------------------------+
#property copyright "Journal Trading Connect"
#property link      "https://github.com/andrizpray/journal-trading-connect"
#property version   "1.00"
#property strict
#property indicator_chart_window  // EA sebagai indicator agar ringan

//--- Input parameters (user isi setelah install)
input string InpServerUrl    = "http://43.134.37.14:8081";
input string InpApiToken     = "";     // API Token dari halaman Connect
input int    InpIntervalSec  = 30;     // Kirim data tiap X detik
input int    InpMaxTrades    = 50;     // Maks trade per request
input bool   InpDebugMode    = false;  // Print log ke Experts tab

//--- Global variables
datetime g_lastSentTime = 0;
int g_lastHistoryCount = 0;
string g_logFilePath = "";

//+------------------------------------------------------------------+
//| Custom indicator initialization                                 |
//+------------------------------------------------------------------+
int OnInit()
{
    // Validasi input
    if(InpApiToken == "") {
        Print("[JTC] ERROR: API Token kosong! Masukkan token dari halaman Connect.");
        return INIT_FAILED;
    }

    // Buat folder untuk log offline
    g_logFilePath = TerminalInfoString(TERMINAL_DATA_PATH)
                  + "\\MQL4\\Files\\jtc_log.csv";

    Print("[JTC] Logger initialized. Server: ", InpServerUrl);
    Print("[JTC] Send interval: ", InpIntervalSec, " seconds");

    // Kirim heartbeat pertama
    SendHeartbeat();

    // Setup timer untuk periodic sync
    EventSetTimer(InpIntervalSec);

    return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
//| Custom indicator deinitialization                                |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    Print("[JTC] Logger stopped.");
}

//+------------------------------------------------------------------+
//| Timer event - periodic sync                                     |
//+------------------------------------------------------------------+
void OnTimer()
{
    SendTradeHistory();
}
```

> **Kenapa indicator bukan EA?** Indicator lebih ringan, tidak mengganggu EA trading user. Indicator bisa jalan bareng EA lain tanpa konflik.

### B2: Fungsi kirim trade history

**Objective:** Baca history dari MT4, kirim ke server.

**Fungsi utama:**
```mql4
void SendTradeHistory()
{
    // 1. Hitung total history
    int total = OrdersHistoryTotal();
    if(total == g_lastHistoryCount) return;  // Tidak ada trade baru
    if(total == 0) return;

    // 2. Ambil trade yang belum dikirim (dari index terakhir)
    int startIndex = MathMax(0, total - InpMaxTrades);

    // 3. Bangun JSON array
    string json = "{";
    json += "\"trades\": [";
    bool first = true;

    for(int i = startIndex; i < total; i++) {
        if(!OrderSelect(i, SELECT_BY_POS, MODE_HISTORY)) continue;

        // Filter: hanya tipe buy/sell (bukan balance/credit)
        int type = OrderType();
        if(type != OP_BUY && type != OP_SELL) continue;

        // Filter: hanya trade yang sudah close
        if(OrderCloseTime() == 0) continue;

        if(!first) json += ",";
        first = false;

        // Hitung durasi
        int duration = (int)(OrderCloseTime() - OrderOpenTime()) / 60;

        // Format trade ke JSON
        json += "{";
        json += "\"ticket\":\"" + IntegerToString(OrderTicket()) + "\",";
        json += "\"open_date\":\"" + TimeToString(OrderOpenTime(), TIME_DATE|TIME_MINUTES) + "\",";
        json += "\"close_date\":\"" + TimeToString(OrderCloseTime(), TIME_DATE|TIME_MINUTES) + "\",";
        json += "\"currency_pair\":\"" + OrderSymbol() + "\",";
        json += "\"trade_type\":\"" + (type == OP_BUY ? "buy" : "sell") + "\",";
        json += "\"lot_size\":" + DoubleToStr(OrderLots(), 2) + ",";
        json += "\"open_price\":" + DoubleToStr(OrderOpenPrice(), (int)MarketInfo(OrderSymbol(), MODE_DIGITS)) + ",";
        json += "\"close_price\":" + DoubleToStr(OrderClosePrice(), (int)MarketInfo(OrderSymbol(), MODE_DIGITS)) + ",";
        json += "\"stop_loss\":" + (OrderStopLoss() > 0 ? DoubleToStr(OrderStopLoss(), (int)MarketInfo(OrderSymbol(), MODE_DIGITS)) : "0") + ",";
        json += "\"take_profit\":" + (OrderTakeProfit() > 0 ? DoubleToStr(OrderTakeProfit(), (int)MarketInfo(OrderSymbol(), MODE_DIGITS)) : "0") + ",";
        json += "\"swap\":" + DoubleToStr(OrderSwap(), 2) + ",";
        json += "\"commission\":" + DoubleToStr(OrderCommission(), 2) + ",";
        json += "\"profit_loss\":" + DoubleToStr(OrderProfit() + OrderSwap() + OrderCommission(), 2) + ",";
        json += "\"duration_minutes\":" + IntegerToString(duration) + ",";
        json += "\"comment\":\"" + OrderComment() + "\"";
        json += "}";
    }

    json += "]}";

    if(first) {
        // Tidak ada trade yang valid
        g_lastHistoryCount = total;
        return;
    }

    // 4. Kirim ke server
    string response = SendRequest("/api/ea/trade/batch", json);

    if(response != "") {
        g_lastHistoryCount = total;
        SaveLogToFile(json);  // Backup lokal
    }
}
```

### B3: Fungsi HTTP request

**Objective:** Kirim HTTP POST ke server Laravel.

```mql4
string SendRequest(string endpoint, string jsonBody)
{
    string url = InpServerUrl + endpoint;
    string contentType = "Content-Type: application/json";
    string authHeader = "Authorization: Bearer " + InpApiToken;
    string headers = contentType + "\r\n" + authHeader;

    string result = "";
    string cookie = "";
    int timeout = 5000;  // 5 detik timeout

    int res = WebRequest("POST", url, contentType, timeout, jsonBody, result, headers);

    if(res == -1) {
        int err = GetLastError();
        Print("[JTC] WebRequest error: ", err);
        Print("[JTC] Pastikan URL ada di 'AllowWebRequest' di Tools > Options > Expert Advisors");
        return "";
    }

    if(InpDebugMode) {
        Print("[JTC] Response (", res, "): ", result);
    }

    return result;
}

string SendHeartbeat()
{
    string json = "{}";
    return SendRequest("/api/ea/heartbeat", json);
}
```

### B4: Fallback log ke file lokal

**Objective:** Kalau server tidak bisa dijangkau, simpan data ke file CSV lokal. Kirim ulang nanti saat online.

```mql4
void SaveLogToFile(string json)
{
    int handle = FileOpen("jtc_log.csv", FILE_WRITE|FILE_READ|FILE_CSV|FILE_SHARE_READ);
    if(handle == INVALID_HANDLE) {
        handle = FileOpen("jtc_log.csv", FILE_WRITE|FILE_CSV|FILE_SHARE_READ);
        if(handle == INVALID_HANDLE) return;
        // Header
        FileWrite(handle, "timestamp", "status");
    }

    // Append ke akhir file
    FileSeek(handle, 0, SEEK_END);
    FileWrite(handle, TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS), "sent");

    FileClose(handle);
}
```

> **Kenapa fallback penting:** Kalau internet user mati sementara, trade tetap tersimpan lokal. Saat online lagi, data bisa di-resync.

### B5: Testing & dokumentasi EA

**Objective:** Panduan install EA untuk user.

**Files:**
- Create: `resources/ea/README.md` — panduan setup EA dalam Bahasa Indonesia

Isi panduan:
1. Download file `JTC_Logger.mq4` atau `JTC_Logger.ex4`
2. Copy ke `MT4 > File > Open Data Folder > MQL4 > Indicators`
3. Buka MetaEditor, compile (atau pakai file .ex4 yang sudah di-compile)
4. Di MT4: drag indicator ke chart
5. Isi parameter: Server URL, API Token
6. Pastikan `Tools > Options > Expert Advisors > Allow WebRequest` → tambahkan URL server
7. Cek tab Experts untuk konfirmasi "[JTC] Logger initialized"

---

## Tahap C — EA Logger MQL5

### C1: Adaptasi ke MQL5

**Objective:** Versi MT5 dari EA Logger yang sama.

**Files:**
- Create: `resources/ea/JTC_Logger.mq5`

**Perbedaan MQL4 vs MQL5 yang perlu diadaptasi:**
- `OrdersHistoryTotal()` → `HistorySelect(0, TimeCurrent())` + `HistoryDealsTotal()`
- `OrderSelect()` → `HistoryDealSelect()` + `HistoryDealGetInteger()`
- `OrderTicket()` → `HistoryDealGetInteger(ticket, DEAL_TICKET)`
- `OrderSymbol()` → `HistoryDealGetString(ticket, DEAL_SYMBOL)`
- `OrderType()` → `HistoryDealGetInteger(ticket, DEAL_TYPE)` (enum berbeda)
- `OrderProfit()` → `HistoryDealGetDouble(ticket, DEAL_PROFIT)`
- `MODE_DIGITS` → `SymbolInfoInteger(symbol, SYMBOL_DIGITS)`

> Semua logika lainnya sama persis dengan MQL4. Hanya fungsi baca history yang berbeda.

---

## Tahap D — UI & Panduan

### D1: Update halaman Connect — ganti card MetaAPI jadi EA Logger

**Objective:** Ganti card "MetaAPI (Soon)" jadi "EA Logger" yang aktif.

**Files:**
- Modify: `resources/views/connect/index.blade.php`

**Isi card:**
- Judul: "EA Logger"
- Subtitle: "Auto-sync dari MT4/MT5"
- Icon: fa-robot
- Status: Active (bukan Soon)
- Tombol: "Setup EA Logger" → ke halaman setup (D2)
- Info: "Install EA Logger di terminal MT4/MT5. Trade otomatis tersinkronisasi ke server."

### D2: Buat halaman EA Logger Setup

**Objective:** Halaman untuk generate token, download EA, panduan setup.

**Files:**
- Create: `resources/views/connect/ea-logger-setup.blade.php`
- Add route: `GET /connect/ea-logger`
- Add controller method: `ConnectController@eaLoggerSetup`

**Isi halaman:**
1. **Pilih Akun Trading** — dropdown akun aktif
2. **API Token** — tampilkan token yang sudah ada (dengan tombol copy), atau tombol "Generate Token Baru"
3. **Download EA** — link download `.mq4` dan `.mq5`
4. **Panduan Setup** — langkah-langkah install EA (collapsible accordion)
5. **Status Koneksi** — menampilkan `last_synced_at`, heartbeat terakhir, jumlah trade yang disinkronkan
6. **Test Koneksi** — tombol untuk test apakah EA bisa konek (POST dummy ke `/api/ea/heartbeat`)

---

## 🔄 KEPUTUSAN YANG PERLU DIAMBIL

1. **Format EA: Indicator vs Expert Advisor?**
   - Rekomendasi: **Indicator** (lebih ringan, tidak konflik dengan EA trading)
   - Kekurangan: user harus attach ke chart manual (bisa di-setiap chart)

2. **MQL4 saja atau MQL4 + MQL5?**
   - Rekomendasi: **Keduanya** — MT4 dan MT5 masih banyak dipakai
   - MQL5 bisa dikerjakan setelah MQL4 jadi (adaptasi, bukan dari nol)

3. **Offline fallback: CSV file di terminal?**
   - Rekomendasi: **Ya** — penting untuk reliability
   - File CSV lokal sebagai backup kalau server tidak bisa dijangkau

4. **HTTPS atau HTTP dulu?**
   - Rekomendasi: **HTTP dulu** — bisa jalan sekarang
   - HTTPS bisa ditambah nanti (Phase 7.5)

---

## 🎯 REKOMENDASI

**Risiko:**
- EA MQL harus di-compile oleh user di MetaEditor mereka sendiri (kita tidak bisa compile dari server). Solusi: sediakan file `.mq4`/`.mq5` source code + instruksi compile jelas.
- `WebRequest()` di MT4 perlu whitelist URL di Tools > Options > Expert Advisors → harus ada di panduan.
- Kalau user punya banyak trade history (>1000), batch request pertama bisa besar. Solusi: EA kirim bertahap (max 50 per request).

**Prioritas eksekusi:**
1. **Tahap A (API Server)** — wajib dulu, karena EA butuh endpoint
2. **Tahap B (EA MQL4)** — develop setelah API jalan dan bisa ditest
3. **Tahap D (UI)** — bisa paralel dengan Tahap B
4. **Tahap C (EA MQL5)** — terakhir, adaptasi dari MQL4
