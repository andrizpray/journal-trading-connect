# Laravel Cross-Database Connection (Read from Another Project)

Pattern for reading data from another Laravel project's database on the same MySQL server. Used when you have two related projects sharing data (e.g., a monitoring app reading from the main app's DB, or a new project importing journal entries from a legacy project).

## Architecture

Two Laravel apps on the same VPS, each with its own database, same MySQL user:

```
App A (port 80)          App B (port 8081)
├── DB: trading_journal  ├── DB: trading_connect
├── journal_entries      ├── journal_entries (own)
├── users                └── connect_controller reads from trading_journal
└── ...
```

## Setup

### 1. Add Connection in `config/database.php`

Under `'connections' => [...]`, add a second connection:

```php
'mysql_journal' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => 'trading_journal',  // target DB name
    'username' => env('DB_USERNAME'),  // same MySQL user (same .env)
    'password' => env('DB_PASSWORD'),  // same password
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
],
```

**Pitfall:** Don't duplicate host/port/user/password — use `env()` references so changes to the main DB connection automatically apply.

### 2. Verify the Target DB Exists

```bash
mysql -u USER -pPASS target_db_name -e "SHOW TABLES;"
```

If the MySQL user doesn't have access, grant it:

```sql
GRANT SELECT ON trading_journal.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Read via `DB::connection()`

```php
use Illuminate\Support\Facades\DB;

// Simple read
$entries = DB::connection('mysql_journal')
    ->table('journal_entries')
    ->orderBy('entry_date', 'desc')
    ->get();

// Check if connection works
try {
    $count = DB::connection('mysql_journal')->table('journal_entries')->count();
    $connected = true;
} catch (\Exception $e) {
    $connected = false;
}
```

### 4. Import Pattern (with Deduplication)

When copying data from the source DB to your app's DB, check for duplicates to prevent re-importing:

```php
foreach ($sourceEntries as $source) {
    // Check duplicate by unique combination
    $exists = JournalEntry::where('user_id', Auth::id())
        ->where('currency_pair', $source->currency_pair)
        ->where('profit_loss', $source->profit_loss)
        ->where('created_at', $source->created_at)
        ->exists();

    if ($exists) { $skipped++; continue; }

    // Map enum values if different between projects
    $result = $source->result;
    if ($result === 'breakeven') $result = 'break_even';  // different enum naming

    JournalEntry::create([
        'user_id' => Auth::id(),
        'currency_pair' => str_replace('/', '', $source->currency_pair),  // normalize format
        // ... map all fields
        'created_at' => $source->created_at,  // preserve original timestamp
        'updated_at' => $source->updated_at,
    ]);
    $imported++;
}
```

## Things to Watch

- **Enum mismatches:** Different projects may use different enum values (e.g., `breakeven` vs `break_even`). Always map before inserting.
- **String format differences:** One project might use `EUR/USD`, another `EURUSD`. Normalize on import.
- **No Eloquent models needed:** Use `DB::connection()->table()` directly — no need to create models for the source DB's tables.
- **Read-only assumption:** This pattern is for importing/copying data. Don't write to the source DB — it belongs to another app.
- **Graceful degradation:** Always wrap in try/catch so the app doesn't crash if the source DB is unavailable (e.g., during development or if the other project is down).
