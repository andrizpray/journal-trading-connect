# Laravel Export: Excel & PDF Pattern

Reusable pattern for adding Excel and PDF export to a Laravel resource, filtering by date range (e.g., 30 days). Uses `maatwebsite/excel` and `barryvdh/dompdf`.

## Prerequisites

```bash
composer require maatwebsite/excel barryvdh/laravel-dompdf
```

## Excel Export Class

Create `app/Exports/JournalExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Journal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function collection()
    {
        return Journal::where('user_id', auth()->id())
            ->where('entry_date', '>=', now()->subDays(30)->startOfDay())
            ->orderBy('entry_date', 'desc')
            ->get()
            ->map(fn($j) => [
                $j->entry_date->format('d M Y'),
                $j->pair,
                $j->type,
                ucfirst($j->result),
                $j->profit_loss,
                $j->market,
                $j->mood,
                $j->analysis ?? '-',
                $j->lesson_learned ?? '-',
            ]);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Pair', 'Tipe', 'Hasil', 'P&L', 'Market', 'Mood', 'Analisis', 'Pelajaran'];
    }

    public function title(): string
    {
        return 'Jurnal Trading';
    }

    public function styles(Worksheet $sheet): void
    {
        // Style header row
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '0891B2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
```

**Pitfall:** If using `FromArray` instead of `FromCollection`, you must return a plain PHP array (not Eloquent collection). `FromCollection` is simpler and auto-serializes.

## PDF Export (Blade View)

Create `resources/views/exports/journal-export-pdf.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jurnal Trading - Export PDF</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #6b7280; font-size: 11px; margin-bottom: 20px; }
        .stats { display: flex; gap: 10px; margin-bottom: 20px; }
        .stat-card { flex: 1; padding: 10px; border-radius: 8px; text-align: center; }
        .stat-value { font-size: 16px; font-weight: bold; }
        .stat-label { font-size: 10px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #0891b2; color: white; padding: 8px 6px; text-align: center; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .win { color: #059669; font-weight: bold; }
        .loss { color: #dc2626; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>📊 Jurnal Trading</h1>
    <p class="subtitle">Periode: {{ $startDate->format('d M Y') }} — {{ $endDate->format('d M Y') }}</p>

    <!-- Stats summary cards -->
    <div class="stats">
        <div class="stat-card" style="background:#ecfdf5;">
            <div class="stat-value">{{ $totalEntries }}</div>
            <div class="stat-label">Total Entry</div>
        </div>
        <div class="stat-card" style="background:#fef3c7;">
            <div class="stat-value">{{ number_format($winRate, 1) }}%</div>
            <div class="stat-label">Win Rate</div>
        </div>
        <div class="stat-card" style="background:{{ $totalPnL >= 0 ? '#ecfdf5' : '#fef2f2' }};">
            <div class="stat-value {{ $totalPnL >= 0 ? 'win' : 'loss' }}">
                {{ number_format($totalPnL, 2) }}
            </div>
            <div class="stat-label">Total P&L</div>
        </div>
    </div>

    <!-- Journal entries table -->
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pair</th>
                <th>Tipe</th>
                <th>Hasil</th>
                <th>P&L</th>
                <th>Analisis</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
            <tr>
                <td>{{ $entry->entry_date->format('d M Y') }}</td>
                <td>{{ $entry->pair }}</td>
                <td>{{ $entry->type }}</td>
                <td class="{{ $entry->result === 'win' ? 'win' : 'loss' }}">{{ ucfirst($entry->result) }}</td>
                <td class="{{ $entry->profit_loss >= 0 ? 'win' : 'loss' }}">{{ number_format($entry->profit_loss, 2) }}</td>
                <td>{{ Str::limit($entry->analysis ?? '-', 80) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d F Y, H:i') }} • Trading Journal Pro
    </div>
</body>
</html>
```

## Controller Methods

Add to your resource controller:

```php
use App\Exports\JournalExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

public function exportExcel()
{
    return Excel::download(new JournalExport, 'jurnal-trading-' . now()->format('Y-m-d') . '.xlsx');
}

public function exportPdf()
{
    $entries = Journal::where('user_id', auth()->id())
        ->where('entry_date', '>=', now()->subDays(30)->startOfDay())
        ->orderBy('entry_date', 'desc')
        ->get();

    $stats = [
        'entries'     => $entries,
        'totalEntries' => $entries->count(),
        'winRate'     => $entries->count() ? ($entries->where('result', 'win')->count() / $entries->count() * 100) : 0,
        'totalPnL'    => $entries->sum('profit_loss'),
        'startDate'   => now()->subDays(30)->startOfDay(),
        'endDate'     => now(),
    ];

    $pdf = Pdf::loadView('exports.journal-export-pdf', $stats)
        ->setPaper('a4', 'portrait');

    return $pdf->download('jurnal-trading-' . now()->format('Y-m-d') . '.pdf');
}
```

## Routes

```php
Route::get('/journal/export/excel', [JournalController::class, 'exportExcel'])->name('journal.export.excel');
Route::get('/journal/export/pdf', [JournalController::class, 'exportPdf'])->name('journal.export.pdf');
```

## UI Buttons (Blade)

Add export buttons in the journal index header — compact icons on mobile, icon+label on desktop:

```html
<a href="{{ route('journal.export.excel') }}"
   class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium transition-colors">
    <i class="bi bi-file-earmark-excel"></i>
    <span class="hidden sm:inline">Excel</span>
</a>
<a href="{{ route('journal.export.pdf') }}"
   class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors">
    <i class="bi bi-file-earmark-pdf"></i>
    <span class="hidden sm:inline">PDF</span>
</a>
```

## Filtered Export (Respects Active Search/Filters)

Pass current request filters to the export class so exported data matches what the user sees on screen:

```php
// Controller
public function export(Request $request)
{
    $filters = $request->only(['search', 'paper_type', 'gsm', 'status', 'sort', 'dir']);
    return Excel::download(new RollItemsExport($filters), 'roll-items-' . date('Y-m-d') . '.xlsx');
}

// Export class — apply same filters as index()
class RollItemsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithChunkReading
{
    public function __construct(array $filters) { $this->filters = $filters; }

    public function query()
    {
        $query = RollItem::query();
        if ($search = $this->filters['search'] ?? null) {
            $query->where('lot_id', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
        }
        if ($pt = $this->filters['paper_type'] ?? null) {
            $query->where('paper_type', $pt);
        }
        return $query->orderBy('lot_id', 'asc');
    }

    public function chunkSize(): int { return 500; }
    // ...
}

// Blade — pass current URL params to export route
<a href="{{ route('items.export', request()->except('page')) }}">Export Excel</a>
```

Key: `request()->except('page')` strips the pagination param (irrelevant for export).

## Large Dataset Export (Chunked, Memory-Safe)

**Pitfall: OOM on large exports.** Exporting 10K+ rows via `FromCollection` loads ALL records into memory at once. On a 256MB PHP limit, 14,875 rows with 25 columns exhausts memory at `vendor/maennchen/zipstream-php/src/File.php:345`.

**Fix — use `FromQuery` + `WithChunkReading`:**

```php
class LargeExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, WithChunkReading
{
    public function query() { return Model::query()->orderBy('id'); }
    public function headings(): array { return [...]; }
    public function map($item): array { return [...]; }
    public function chunkSize(): int { return 500; }  // Process 500 rows at a time
}
```

**Also bump memory limit in the controller** for the export route only:

```php
public function export(Request $request)
{
    ini_set('memory_limit', '512M');
    return Excel::download(new LargeExport($filters), 'file.xlsx');
}
```

- `FromQuery` + `WithChunkReading` processes rows in batches → constant memory
- `FromCollection` loads everything at once → OOM risk on 10K+ rows
- `chunkSize()` of 500 is a good balance (adjust based on row width)
- Always combine with `ini_set('memory_limit', '512M')` as a safety net

## Multi-Sheet Export (WithMultipleSheets)

For summary reports with multiple sheets (overview, detail data, breakdowns):

```php
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SummaryReportExport implements WithMultipleSheets
{
    public function __construct(string $year, string $month = '') { ... }

    public function sheets(): array
    {
        return [
            'Ringkasan' => new SummaryOverviewSheet($this->year, $this->month),
            'Defects'   => new SummaryDefectsSheet($this->year, $this->month),
            'Paper Type' => new SummaryPaperTypeSheet($this->year, $this->month),
        ];
    }
}

// Each sheet is its own class implementing FromCollection, WithHeadings, WithStyles, WithTitle
class SummaryOverviewSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $rows = collect();
        $rows->push(['Total Rolls', number_format(RollItem::count())]);
        $rows->push(['Total Defects', number_format(DefectItem::count())]);
        $rows->push(['']);  // Blank row as separator
        $rows->push(['--- Top Reasons ---', '']);
        // ...
        return $rows;
    }
}
```

Each sheet class gets its own styled header (`WithStyles`) — use different header colors per sheet for visual distinction (blue for overview, red for defects, purple for breakdowns).

## Route Ordering Pitfall

**Pitfall:** `/items/export` returns 404 when registered AFTER `/items/{id}`. Laravel matches `{id}` first, treating "export" as an ID value → `findOrFail('export')` → 404.

**Fix:** Register literal routes BEFORE parameter routes:

```php
// CORRECT — export route first
Route::get('/items/export', [RollItemController::class, 'export'])->name('items.export');
Route::get('/items/{id}', [RollItemController::class, 'show'])->name('items.show');

// WRONG — {id} swallows "export"
Route::get('/items/{id}', [RollItemController::class, 'show'])->name('items.show');
Route::get('/items/export', [RollItemController::class, 'export'])->name('items.export');  // 404!
```

This applies to ANY route like `/items/create`, `/items/import`, `/items/export` that conflicts with `/{id}`.

## Adding a New Filter: Full Sync Checklist

When adding a new filter dropdown (e.g., "grade"), you must update ALL of these places or the UX breaks silently:

1. **Controller `index()`** — add `if ($x = $request->input('field'))` query filter
2. **Controller `index()`** — add dropdown data query (`$grades = Model::whereNotNull('grade')...`)
3. **Controller `index()`** — add `$grades` to `compact()` view variables
4. **Controller `export()`** — add `'grade'` to `$request->only([...])` filter list
5. **Export class `query()`** — add the same filter logic (must mirror controller)
6. **View: Reset button** — add `request('grade')` to the `@if(...)` condition
7. **View: Filter auto-show** — add to BOTH the `class="hidden {{ ... }}"` condition AND the `@if(!request('grade') ...)` script
8. **View: Filter grid** — add the `<select>` dropdown (update `grid-cols-N` if column count changes)
9. **View: Export URL** — `request()->except('page')` auto-includes new param ✅

**Miss any one of these and:** filter applies but Reset button doesn't appear, or export ignores the filter, or the filter panel doesn't auto-open when a filter is active.

## Summary Report Modal (Year + Month Picker)

For multi-sheet reports where the user picks parameters:

```blade
<!-- Modal -->
<div id="summaryModal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
        <select id="summaryYear">
            <option value="2025">2025</option><option value="2026" selected>2026</option>
        </select>
        <select id="summaryMonth">
            <option value="">Semua Bulan</option>
            @foreach($months as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
        </select>
        <button onclick="downloadSummary()">Download</button>
        <button onclick="closeSummaryModal()">Batal</button>
    </div>
</div>

<script>
function openSummaryModal() { document.getElementById('summaryModal').style.display = 'flex'; }
function closeSummaryModal() { document.getElementById('summaryModal').style.display = 'none'; }
function downloadSummary() {
    const year = document.getElementById('summaryYear').value;
    const month = document.getElementById('summaryMonth').value;
    let url = '{{ route("defects.summary") }}?year=' + year;
    if (month) url += '&month=' + encodeURIComponent(month);
    window.location.href = url;
    closeSummaryModal();
}
document.getElementById('summaryModal').addEventListener('click', function(e) {
    if (e.target === this) closeSummaryModal();
});
</script>
```

## Key Notes

- **30-day filter** uses `now()->subDays(30)->startOfDay()` — adjust as needed
- **Always scope by `auth()->id()`** — never export another user's data (unless no-auth internal tool)
- **PDF styling** uses inline CSS in the Blade view (DOMPDF doesn't process external stylesheets reliably)
- **Excel auto-size** columns via `setAutoSize(true)` — works well for variable-length content
- **File naming** includes date (`Y-m-d`) to prevent browser cache confusion
- **Use `FromQuery` + `WithChunkReading`** for 5K+ row exports to prevent OOM
- **Register export routes BEFORE `/{id}` parameter routes** to avoid 404 conflicts

## Critical Pitfall: FromArray vs FromCollection + toArray()

**This is the #1 source of runtime errors in Maatwebsite Excel exports.**

### The Problem

If your export class uses `FromArray` (needed when you want summary/total rows after the data), and the controller passes `$entries->toArray()`, the export class receives **plain PHP arrays**, not Eloquent model objects. But if you wrote the export class using object syntax (`$entry->field`), it will throw:

```
ErrorException: Attempt to read property "field" on array
```

### The Fix

**Option A — Send the Collection, not array (recommended):**
```php
// Controller — do NOT call ->toArray()
return Excel::download(new JournalExport($entries, $stats, $periodLabel), 'file.xlsx');

// Export class — use loose type hints (no `array` or `object` type)
private $entries;  // NOT: private array $entries;
public function __construct($entries, $stats, string $periodLabel) { ... }

// In array() method — object syntax works because entries are still Eloquent models
$entry->emotion_score
```

**Option B — Use FromCollection instead of FromArray (simpler, but no summary rows):**
```php
class JournalExport implements FromCollection, WithHeadings { ... }
// collection() returns a mapped Collection, no toArray() needed
```

### When to use FromArray vs FromCollection

| Scenario | Use |
|----------|-----|
| Simple data rows only | `FromCollection` (simpler) |
| Data rows + summary/total rows at bottom | `FromArray` (build mixed array) |
| Conditional row formatting | `FromArray` (full control) |

### Rule of thumb

If the export class references `$entry->field` (object arrow syntax), it expects **Eloquent model objects**, not plain arrays. Either:
- Pass the raw Collection from the controller (don't call `->toArray()`), OR
- Change all `$entry->field` to `$entry['field']` in the export class
