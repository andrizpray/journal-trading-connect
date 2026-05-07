# Laravel CSV Import — Multi-Format (MT4/MT5, TradingView, cTrader)

Pattern for importing trade history from multiple CSV formats into a Laravel application. Used in Journal Trading Connect project.

## Supported Formats

**MT4/MT5 standard:** `Ticket, Open Date, Close Date, Type, Lot, Symbol, Open Price, Close Price, SL, TP, Swap, Commission, Profit, Comment`

**MT5 Report History:** Different from standard CSV — has metadata rows before data:
```
Trade History Report
Name:,,,ANDRIS PRAYOGA
Account:,,,"24996648 (USD, Vantage, demo)"
...
Positions
Time,Position,Symbol,Type,Volume,Price,S / L,T / P,Time,Price,Commission,Swap,Profit,,
2026.04.13 12:44:42,1157205300,XAUUSD,buy,0.01,4 714.10,,4 717.26,...
```
Key differences: (1) header row is NOT the first row — scan for "Positions" or "Closed Positions" line first; (2) prices use **space as thousands separator** (e.g., `4 714.10` = 4714.10); (3) column names have spaces (`S / L`, `T / P`, `Position`); (4) dates use `Y.m.d H:i:s` format.

**TradingView:** `Type, Symbol, Qty, Entry Price, Close Price, P&L` — note: no ticket column, no dates. Ticket is auto-generated.

**cTrader:** `Trade ID, Symbol, Side, Quantity, Open Time, Close Time, Open Price, Close Price, Gross P/L, Swap, Commission, Net P/L`

Column names vary by broker/platform. Use flexible column mapping (see below). Only `Symbol` and `Profit` are required.

## Architecture

```
ImportController (handles upload, parsing, storage)
  ├── import()         — detects format, validates, parses rows, stores trades
  ├── mapColumns()     — auto-detect column positions from header
  ├── parseDate()      — handle multiple date formats
  ├── normalizeTradeType() — normalize "buy limit" → "buy_limit"
  └── parseFloat()     — handle locale-specific number formats (space thousands)
```

### MT5 Report Format Detection

MT5 Report CSV files have metadata rows before the actual data header. The import method must scan for the "Positions" marker:

```php
// Detect MT5 report format (has "Positions" header before actual data)
$headerRowIdx = 0;
$startRowIdx = 1;
foreach ($allRows as $idx => $row) {
    $firstCell = strtolower(trim($row[0] ?? ''));
    if (strpos($firstCell, 'positions') !== false || strpos($firstCell, 'closed positions') !== false) {
        $headerRowIdx = $idx + 1;
        $startRowIdx = $idx + 2;
        break;
    }
}

$rows = array_slice($allRows, $headerRowIdx);
$header = array_map('trim', array_map('strtolower', $rows[0] ?? []));

// Start parsing from $startRowIdx (relative to $rows)
for ($i = $startRowIdx - $headerRowIdx; $i < count($rows); $i++) { ... }
```

Also stop parsing when hitting the next section marker (e.g., "Closed Positions", "Orders", "Deals"):

```php
$line = trim($lines[$i]);
if (strpos($line, 'Closed Positions') !== false || strpos($line, 'Orders') !== false || strpos($line, 'Deals') !== false) {
    break;
}
```

### Space Thousands in Prices

MT5 Report prices use space as thousands separator: `4 714.10` means 4714.10. The `parseFloat()` helper already handles this via `str_replace(' ', '', $value)`, but when parsing prices directly (not through parseFloat), always strip spaces:

```php
$openPrice = (float) str_replace(' ', '', trim($parts[5]));
$closePrice = (float) str_replace(' ', '', trim($parts[9]));
$profit = (float) str_replace([',', ' '], '', trim($parts[12]));
```

### Bulk Import via PHP Script (for large CSVs)

For CSV files with 1000+ rows (e.g., full MT5 report history with 3000+ trades), web upload may timeout even with increased `max_execution_time`. A direct PHP script using Laravel's bootstrap is more reliable:

```php
<?php
require '/path/to/project/vendor/autoload.php';
$app = require_once '/path/to/project/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Now use DB, Carbon, models — everything works
$lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $parts = str_getcsv($line);
    // ... parse and insert via \DB::table('trade_histories')->insert([...]);
}
```

Run with: `php /tmp/import_script.php` (no timeout from web server).

## Key Patterns

### 1. Flexible Column Mapping

Don't assume column order. Map by checking multiple aliases per field:

```php
private function mapColumns(array $header): ?array
{
    $mapping = [
        'ticket' => ['ticket', 'order', 'order ticket', 'order #', 'trade id', 'position id', 'id', 'position'],
        'open_date' => ['open date', 'opentime', 'open time', 'open_date', 'datetime', 'open time (cet)', 'entry time'],
        'close_date' => ['close date', 'closetime', 'close time', 'close_date', 'time', 'close time (cet)', 'close time', 'exit time'],
        'type' => ['type', 'trade type', 'direction', 'side', 'action'],
        'lot' => ['lot', 'lots', 'lotsize', 'lot size', 'volume', 'qty', 'quantity', 'size', 'amount'],
        'symbol' => ['symbol', 'pair', 'currency pair', 'instrument', 'currency_pair', 'ticker'],
        'open_price' => ['open price', 'openprice', 'open', 'price open', 'entry price', 'avg entry price'],
        'close_price' => ['close price', 'closeprice', 'close', 'price close', 'exit price', 'avg close price'],
        'sl' => ['sl', 'stop loss', 's/l', 'stoploss', 'stop loss price', 's / l'],
        'tp' => ['tp', 'take profit', 't/p', 'takeprofit', 'take profit price', 't / p'],
        'swap' => ['swap', 'swaps', 'rollover', 'financing'],
        'commission' => ['commission', 'commissions', 'comm', 'fee', 'fees'],
        'profit' => ['profit', 'p&l', 'pnl', 'pl', 'result', 'profit/loss', 'net profit', 'net p/l', 'gross p/l', 'total p/l', 'gain', 'return'],
        'comment' => ['comment', 'comments', 'notes', 'remarks', 'description', 'label'],
    ];

    $result = [];
    foreach ($mapping as $field => $aliases) {
        $found = false;
        foreach ($aliases as $alias) {
            $index = array_search($alias, $header);
            if ($index !== false) {
                $result[$field] = $index;
                $found = true;
                break;
            }
        }
        // Required fields — symbol and profit are required
        // ticket can be auto-generated if missing
        if (!$found && in_array($field, ['symbol', 'profit'])) {
            return null;
        }
    }

    // Auto-generate ticket column if not found (TradingView etc.)
    if (!isset($result['ticket'])) {
        $result['ticket'] = false; // will be auto-generated per row
    }

    return $result;
}
```

### 2. Skip Duplicates via Unique Constraint

Migration:
```php
$table->unique(['user_id', 'trading_account_id', 'ticket']);
```

In import loop:
```php
$exists = TradeHistory::where('user_id', Auth::id())
    ->where('trading_account_id', $account->id)
    ->where('ticket', $ticket)
    ->exists();
if ($exists) { $skipped++; continue; }
```

### 3. Auto-Calculate Trade Result from P&L

```php
$result = 'break_even';
if ($profitLoss > 0) $result = 'win';
elseif ($profitLoss < 0) $result = 'loss';
```

### 4. Calculate Trade Duration

```php
$duration = null;
if ($openDate && $closeDate) {
    $duration = $openDate->diffInMinutes($closeDate);
}
```

### 5. Multiple Date Format Parsing

```php
private function parseDate(string $value): ?\Carbon\Carbon
{
    if (empty($value)) return null;
    $formats = [
        'Y-m-d H:i:s',      // Standard MySQL datetime
        'Y.m.d H:i:s',      // MT4 format (2026.01.15 08:30:00)
        'd.m.Y H:i:s',      // European (15.01.2026 08:30:00)
        'd/m/Y H:i:s',      // European slash (15/01/2026 08:30:00)
        'm/d/Y H:i:s',      // US (01/15/2026 08:30:00)
        'Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y',  // Date-only variants
        'M d, Y H:i:s',     // TradingView (Jan 15, 2026 08:30)
        'M d Y H:i:s',      // TradingView variant (Jan 15 2026 08:30)
        'Y/m/d H:i:s',      // ISO slash (2026/01/15 08:30:00)
        'd-M-Y H:i:s',      // cTrader (15-Jan-2026 08:30:00)
    ];
    foreach ($formats as $format) {
        try { return \Carbon\Carbon::createFromFormat($format, trim($value)); }
        catch (\Exception $e) { continue; }
    }
    try { return new \Carbon\Carbon($value); }
    catch (\Exception $e) { return null; }
}
```

### 6. Update Account Stats After Import

```php
$account->update([
    'last_synced_at' => now(),
    'total_trades' => TradeHistory::where('trading_account_id', $account->id)->count(),
    'total_pnl' => TradeHistory::where('trading_account_id', $account->id)->sum('profit_loss'),
]);
```

### 7. Auto-Create Journal Entries from Imported Trades

```php
if ($autoJournal) {
    JournalEntry::create([
        'user_id' => Auth::id(),
        'trade_history_id' => $trade->id,
        'currency_pair' => $trade->currency_pair,
        'trade_type' => in_array($trade->trade_type, ['buy_limit', 'buy_stop']) ? 'buy' : 
                        (in_array($trade->trade_type, ['sell_limit', 'sell_stop']) ? 'sell' : $trade->trade_type),
        'profit_loss' => $profitLoss,
        'result' => $result,
        'auto_imported' => true,
    ]);
}
```

## Validation

- File: `mimes:csv,txt|max:10240` (10MB max)
- Account: `exists:trading_accounts,id` (must belong to user)
- Required CSV columns: **symbol** and **profit** only (ticket is optional — auto-generated if missing)
- Non-required: everything else defaults to null/0

## Auto-Generate Ticket

Some formats (TradingView) don't have a ticket/order ID column. When `mapColumns()` returns `false` for ticket:

```php
$ticketCol = $colMap['ticket'];
$ticket = ($ticketCol !== false) ? trim($row[$ticketCol] ?? '') : '';

// Skip empty rows (only when ticket column exists)
if (empty($ticket) && $ticketCol !== false) continue;

// Auto-generate ticket if column not present
if (empty($ticket)) {
    $ticket = 'tv_' . $i . '_' . time();
}
```

## UI Pattern

Drag-and-drop upload zone with Alpine.js:
```html
<div x-data="{ dragging: false }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false">
    <div class="drop-zone" :class="{ 'active': dragging }" onclick="this.querySelector('input[type=file]').click()">
        <input type="file" name="csv_file" accept=".csv,.txt" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
        <!-- Drop zone content -->
    </div>
</div>
```

## Download Template Route

```php
public function template()
{
    $headers = ['Ticket', 'Open Date', 'Close Date', 'Type', 'Lot', 'Symbol', 'Open Price', 'Close Price', 'SL', 'TP', 'Swap', 'Commission', 'Profit', 'Comment'];
    $sample = ['123456', '2026-01-15 08:30:00', '2026-01-15 10:45:00', 'buy', '0.10', 'EURUSD', '1.08500', '1.08750', '1.08300', '1.09000', '-1.25', '0.00', '25.00', 'Sample trade'];
    $content = implode(',', $headers) . "\n" . implode(',', $sample);
    return response($content, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="trade_history_template.csv"']);
}
```
