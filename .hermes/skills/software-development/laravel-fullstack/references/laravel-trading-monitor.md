# Laravel Trading Monitor — Full Implementation Pattern

A dedicated Laravel app that monitors another Laravel project by sharing its MySQL database. Used for journal-trading-connect monitoring at `http://43.134.37.14:8080`.

## Architecture

```
Target App (port 8081)          Monitor App (port 8080)
journal-trading-connect         trading-monitor
├── users                       ├── admins (own table)
├── trade_histories             ├── monitor_snapshots (own table)
├── journal_entries             └── models → read from target DB
├── trading_accounts
└── import_logs                 

Both apps share same MySQL DB: trading_connect
```

## Setup Steps

### 1. Create the Monitor Project

```bash
composer create-project laravel/laravel trading-monitor
cd trading-monitor
cp .env.example .env
php artisan key:generate
```

### 2. Configure `.env`

```env
APP_NAME="Trading Monitor"
APP_URL=https://eatrade-journal.site
ROUTE_PREFIX=monitor
DB_CONNECTION=mysql
DB_DATABASE=trading_connect    # SAME DB as target app
DB_USERNAME=laravel
DB_PASSWORD=laravel123
SESSION_DRIVER=file            # Use file, not database (monitor has its own session)
```

### 3. Delete Default Migrations

Default migrations (`users`, `cache`, `jobs`, `sessions`) conflict with existing tables:

```bash
rm database/migrations/0001_01_01_*.php
```

### 4. Create Monitor-Specific Migrations

Only tables unique to the monitor app:

```bash
# Admin auth
php artisan make:migration create_admins_table
# Schema: id, name, email (unique), password, last_login_at (nullable), timestamps

# Daily metrics snapshots
php artisan make:migration create_monitor_snapshots_table
# Schema: id, snapshot_date (unique), total_users, active_users_today/week/month,
#   new_users_today, total_entries, total_pnl, avg_pnl, win_rate,
#   cpu_percent, ram_percent, disk_percent, disk_used_gb, timestamps

php artisan migrate --force
```

### 5. Create Admin User

```bash
php artisan tinker --execute="
    \App\Models\Admin::create([
        'name' => 'Admin',
        'email' => 'admin@monitor.local',
        'password' => bcrypt('admin123')
    ]);
"
```

### 6. Auth Guard Configuration

`config/auth.php`:
```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'admin' => ['driver' => 'session', 'provider' => 'admins'],
],
'providers' => [
    'users' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'admins' => ['driver' => 'eloquent', 'model' => App\Models\Admin::class],
],
```

### 7. Nginx Multi-Port Setup

```bash
sudo tee /etc/nginx/sites-available/trading-monitor > /dev/null << 'EOF'
server {
    listen 8080;
    server_name _;
    root /home/ubuntu/trading-monitor/public;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;
    charset utf-8;
    client_max_body_size 25M;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }
    location ~ /\.(?!well-known).* { deny all; }
}
EOF
sudo ln -sf /etc/nginx/sites-available/trading-monitor /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 8. Storage Permissions (Critical)

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 777 storage bootstrap/cache
```

CLI commands run as `ubuntu`, PHP-FPM runs as `www-data`. On dev VPS, `chmod 777` avoids the constant permission dance. Production should use group ACLs.

## Key Models

### User (reads target app's users table)
```php
class User extends Model {
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'role', ...];
    public function tradeHistories(): HasMany { ... }
    public function tradingAccounts(): HasMany { ... }
}
```

### TradeHistory (reads target app's trade_histories)
```php
class TradeHistory extends Model {
    protected $table = 'trade_histories';
    protected $casts = [
        'open_date' => 'date', 'close_date' => 'date',
        'lot_size' => 'decimal:2', 'profit_loss' => 'decimal:2',
    ];
}
```

### MonitorSnapshot (monitor's own table)
```php
class MonitorSnapshot extends Model {
    protected $table = 'monitor_snapshots';
    protected $fillable = [
        'snapshot_date', 'total_users', 'total_entries',
        'total_pnl', 'win_rate', 'cpu_percent', 'ram_percent', ...
    ];
}
```

## Dashboard Pages

### Overview (index)
- 8 metric cards: users, active today, total trades, win rate, P&L, lots, accounts, server health
- Charts: user growth (30d), daily trades (7d), daily P&L (7d)
- Top currency pairs table
- Recent trades table (last 20)

### Users Page
- Registration chart (30d)
- DAU/WAU/MAU metrics (from `trade_histories.close_date`)
- User list with trade count, P&L, account count
- Top 10 traders leaderboard

### Portfolio Page
- Per-user P&L ranking
- Top 10 by P&L (bar chart)
- Per-pair P&L breakdown across all users

### Server Page
- Live CPU/RAM/Disk from Prometheus API
- Historical metrics from `monitor_snapshots` (7d line chart)
- Server info: PHP version, MySQL version, Nginx version, OS, uptime, disk free

### Logs Page
- Aggregates logs from ALL Laravel apps on the VPS
- Filter by level (ERROR, WARNING, CRITICAL) AND source (app name)
- Expandable stack traces
- Pagination

## Prometheus Integration

Query Prometheus API for server metrics:
```php
private function queryPrometheus(string $query): float
{
    try {
        $response = Http::timeout(5)->get('http://127.0.0.1:9090/api/v1/query', [
            'query' => $query,
        ]);
        $data = $response->json();
        if (($data['status'] ?? '') === 'success' && !empty($data['data']['result'])) {
            return round((float) $data['data']['result'][0]['value'][1], 1);
        }
    } catch (\Throwable $e) {}
    return 0;
}

// CPU usage
$this->queryPrometheus('100 - (avg by(instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)');
// RAM usage
$this->queryPrometheus('(1 - node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes) * 100');
// Disk usage
$this->queryPrometheus('(1 - node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"}) * 100)');
```

Prometheus must be bound to `127.0.0.1` only — never expose publicly.

## Daily Snapshot Cron

Use a dedicated artisan command (cleaner than inline closure in Kernel):

```bash
php artisan make:command TakeDailySnapshot
```

The command computes all metrics (user counts, trade stats, server metrics from Prometheus), creates/updates a `monitor_snapshots` row for today, and displays a summary table. It skips if a snapshot already exists for the date.

**Register in crontab:**
```bash
(crontab -l 2>/dev/null; echo "0 0 * * * cd /home/ubuntu/trading-monitor && php artisan monitor:snapshot >> /dev/null 2>&1") | crontab -
```

**Test manually:** `php artisan monitor:snapshot`

**Pitfall:** After switching `DB_DATABASE` in `.env`, run `php artisan config:clear` before testing — stale cached config may point to the old database. Also `php artisan view:clear` if views were previously compiled against different controller data.

**Pitfall: User reports login failure but credentials are correct.** This is almost always a storage permission issue, not a credential issue. Debugging sequence:
1. Verify credentials: `php artisan tinker --execute="echo Auth::guard('admin')->attempt(['email'=>'admin@monitor.local','password'=>'admin123']) ? 'OK' : 'FAIL';"`
2. Check nginx error log: `sudo tail -5 /var/log/nginx/error.log` — look for "Permission denied"
3. Fix permissions: `sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 777 storage bootstrap/cache`
4. Reload PHP-FPM: `sudo systemctl reload php8.3-fpm`
5. Verify from browser: tell user to open incognito window and test
6. If still failing, clear sessions: `rm -f storage/framework/sessions/*` and `php artisan cache:clear`

**Admin credential reset** (if user genuinely forgot or you need to reset):
```bash
php artisan tinker --execute="
\App\Models\Admin::truncate();
\App\Models\Admin::create(['name'=>'Admin','email'=>'admin@monitor.local','password'=>bcrypt('admin123')]);
"
php artisan cache:clear
rm -f storage/framework/sessions/*
```

## Multi-App Log Aggregation Pattern

Read logs from multiple Laravel apps, tag with source, merge and sort:

```php
$logPaths = [
    '/home/ubuntu/journal-trading-connect/storage/logs/laravel.log' => 'JTC Connect',
    '/home/ubuntu/jurnal-trading/storage/logs/laravel.log' => 'Jurnal Trading',
];
$allEntries = [];
foreach ($logPaths as $path => $sourceName) {
    if (!file_exists($path)) continue;
    $lines = array_slice(file($path), -3000); // last 3000 lines
    // Parse entries, tag with $sourceName
}
// Sort by date descending, paginate, filter by level + source
```

## Notes

- **Prometheus is optional.** If Prometheus is not running, `queryPrometheus()` gracefully returns 0 for all server metrics. The dashboard still works for trading data. Install Prometheus + Node Exporter separately if you want live CPU/RAM/Disk gauges.
- **After switching DB in `.env`, always run `php artisan config:clear`.** Stale cached config causes queries against the old database.
- **Storage must be `www-data:www-data` with `chmod 777`.** CLI (artisan/tinker) runs as `ubuntu`, PHP-FPM runs as `www-data`. Without 777, one of them will get "Permission denied".
- **Login POST returns 419 in curl tests** when the CSRF token is obtained in a separate request (different session). This is normal — use `php artisan tinker` to verify login works.
- **GitHub:** `git@github.com:andrizpray/trading-monitor.git`
- **Path:** `/home/ubuntu/trading-monitor` (NOT `/var/www/` — previous sessions had this wrong)
- **Access:** `https://eatrade-journal.site/monitor` (path-based reverse proxy from main nginx, NOT direct port 8080)
- **Route prefix:** `ROUTE_PREFIX=monitor` in `.env`, all routes wrapped with `Route::prefix(env('ROUTE_PREFIX', ''))`
- **APP_URL:** `https://eatrade-journal.site` (base domain only — do NOT include `/monitor` or routes will double-prefix)
