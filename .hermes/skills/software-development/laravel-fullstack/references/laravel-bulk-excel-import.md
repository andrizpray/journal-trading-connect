# Laravel Bulk Excel Import — Chunked Processing

Pattern for importing large Excel files (10K+ rows) via artisan command with chunked batch inserts, Excel serial date conversion, and description parsing. Used in Roll Off Management project (14,875 rows imported from multi-sheet .xlsx).

## Architecture

```
php artisan import:excel {file}
  ├── ImportExcelData command
  │   ├── importDataSheet()        — main inventory (chunked upsert)
  │   ├── importDefect2025()       — defect tracking year 2025
  │   ├── importDefect2026()       — defect tracking year 2026
  │   ├── parseDescription()       — extract PaperType, GSM, Plybond, Width
  │   ├── excelSerialToDate()      — Excel serial number → Y-m-d
  │   └── excelFractionToTime()    — Excel fraction → HH:MM:SS
  └── Models: RollItem, DefectItem
```

## Setup

```bash
composer require maatwebsite/excel
php artisan make:command ImportExcelData
php artisan make:model RollItem
php artisan make:model DefectItem
```

## Key Patterns

### 1. Chunked Batch Insert (for 10K+ rows)

Web upload will timeout. Use an artisan command with chunked processing:

```php
$chunkSize = 500;
for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkSize) {
    $endRow = min($startRow + $chunkSize - 1, $totalRows);
    $batch = [];

    for ($row = $startRow; $row <= $endRow; $row++) {
        // Build batch array...
        $batch[] = [...];
    }

    if (!empty($batch)) {
        RollItem::upsert($batch, ['lot_id'], array_keys($batch[0]));
    }

    $this->output->write("\r  Progress: {$endRow}/{$totalRows}");
}
```

**Pitfall:** `Model::insert()` silently ignores duplicates. Use `upsert()` with unique key for idempotent imports (safe to re-run). The 3rd arg is the list of updatable columns — pass ALL columns so updates work on re-import.

**Pitfall:** Don't use `DefectItem::insert()` for rows that may have duplicates across runs — `insert()` fails on unique constraint violation. Use `create()` in a loop or `upsert()` with proper unique keys.

### 2. Excel Serial Date Conversion

Excel stores dates as serial numbers (1 = Jan 1, 1900). PhpSpreadsheet usually converts them automatically, but raw numeric values from `getReadDataOnly(true)` may still be serials.

```php
private function excelSerialToDate(?string $serial): ?string
{
    if (!$serial || !is_numeric($serial)) return null;
    $days = (int) round((float) $serial); // round() avoids float→int deprecation in PHP 8.3
    if ($days < 1) return null;
    try {
        $date = Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($days);
        return $date->format('Y-m-d');
    } catch (\Throwable $e) {
        return null;
    }
}
```

**Pitfall — PHP 8.3 float→int deprecation:** Excel serial dates from `getReadDataOnly(true)` can be floats like `30239.999999999996`. Casting directly `(int) $serial` triggers `DEPRECATED: Implicit conversion from float to int loses precision`. Use `(int) round((float) $serial)` instead.

Note: Base date is `1899-12-30` (not `1899-12-31`) because Excel has a deliberate bug treating 1900 as a leap year.

### 3. Excel Time Fraction Conversion

Excel stores time as a fraction of 24 hours (0.5 = noon, 0.75 = 6PM):

```php
private function excelFractionToTime(?string $fraction): ?string
{
    if (!$fraction || !is_numeric($fraction)) return null;
    $totalSeconds = (float) $fraction * 86400;
    $hours = (int) floor($totalSeconds / 3600);
    $minutes = (int) floor(($totalSeconds % 3600) / 60);
    $seconds = (int) ($totalSeconds % 60);
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
```

### 4. Description Parsing (Extract Structured Fields)

When a free-text description column contains structured data (e.g., "B KRAFT BK125 E150 690"):

```php
private function parseDescription(?string $description): array
{
    $result = ['paper_type' => null, 'gsm' => null, 'plybond' => null, 'width' => null];
    if (empty($description)) return $result;

    // Pattern: "B KRAFT BK125 E150 690"
    // → paper_type=B KRAFT, gsm=BK125, plybond=E150, width=690
    if (preg_match('/^(.+?)\s+(BK\d{2,3})\s+(E\d{2,3})\s+(\d+)\s*$/',
                   trim($description), $m)) {
        $result['paper_type'] = strtoupper(trim($m[1]));
        $result['gsm'] = strtoupper($m[2]);
        $result['plybond'] = strtoupper($m[3]);
        $result['width'] = $m[4];
    }

    return $result;
}
```

Store parsed fields in separate DB columns with indexes for fast filtering.

#### Typo Normalization (Post-Processing)

Dirty Excel data often contains typos that are consistent but won't match clean patterns. Don't add typo variants to the pattern list — normalize AFTER matching:

```php
$result['paper_type'] = strtoupper($paperType);

// Fix known typos from Excel data
$result['paper_type'] = str_replace('CORE BORAD', 'CORE BOARD', $result['paper_type']);
$result['paper_type'] = str_replace('COATED DUPLEX OCT', 'COATED DUPLEX OCT', $result['paper_type']);
```

**Also fix existing DB records after updating the parser:**
```php
php artisan tinker --execute="App\Models\RollItem::where('paper_type', 'CORE BORAD')->update(['paper_type' => 'CORE BOARD']);"
```

#### Sentinel Value Convention: `-` Means Empty

Many Excel-sourced fields use `-` as "no value" instead of actual empty/NULL. This affects:

- **Filter dropdowns:** exclude `-` values: `->where('grade', '!=', '-')->where('grade', '!=', '')`
- **View display:** `@if($item->grade && $item->grade != '-')` — don't show `-` to users
- **DB queries:** don't filter by `-` thinking it's a real value

Always check a column's unique values before building filters — if `-` appears, treat it as NULL.

### 5. Multi-Sheet Import

Excel files may have multiple sheets with different structures. Read sheet names first, then handle each:

```php
$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$wb = $reader->load($file);

foreach ($wb->getSheetNames() as $name) {
    $sheet = $wb->getSheetByName($name);
    // Handle per sheet...
}
```

**Pitfall:** Different sheets may have different column orders. Never assume column positions are the same across sheets — always check headers per sheet.

### 6. Model Fillable

When using `create()` or `upsert()` with arrays, the model must have `$fillable` defined:

```php
class RollItem extends Model
{
    protected $fillable = ['lot_id', 'item_id', 'end_qty', 'description', ...];
}
```

**Pitfall:** `MassAssignmentException` if fillable is missing or incomplete. Always define ALL columns that the import writes to.

## Data Exploration Before Import

Always read the Excel file structure first before writing the import command:

```php
php artisan tinker --execute="
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile('/path/to/file.xlsx');
$reader->setReadDataOnly(true);
$wb = $reader->load('/path/to/file.xlsx');

// Sheet names and sizes
foreach ($wb->getSheetNames() as $name) {
    $sheet = $wb->getSheetByName($name);
    echo \"$name: {$sheet->getHighestRow()} rows, {$sheet->getHighestColumn()} cols\n\";
}

// Headers
for ($col = 1; $col <= 21; $col++) {
    echo 'Col ' . $col . ': ' . ($sheet->getCellByColumnAndRow($col, 1)->getValue() ?? 'NULL') . \"\n\";
}

// Unique values (for enum-like columns)
$vals = [];
for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
    $v = $sheet->getCellByColumnAndRow(12, $row)->getValue();
    if ($v) $vals[] = (string)$v;
}
echo 'Unique: ' . implode(', ', array_unique($vals)) . \"\n\";
"
```

**Pitfall:** `tinker` may timeout on large sheets. Limit row reads with `min($sheet->getHighestRow(), 20)` for exploration.

## Command Registration

Register in `routes/console.php`:
```php
use App\Console\Commands\ImportExcelData;
Schedule::command('import:excel')->withoutOverlapping();
```

Or run manually: `php artisan import:excel /path/to/file.xlsx`

## Verification After Import

```sql
SELECT COUNT(*) FROM roll_items;
SELECT COUNT(*) FROM defect_items;
SELECT paper_type, COUNT(*) FROM roll_items GROUP BY paper_type ORDER BY COUNT(*) DESC LIMIT 10;
SELECT reason, COUNT(*) FROM defect_items GROUP BY reason ORDER BY COUNT(*) DESC;
```
