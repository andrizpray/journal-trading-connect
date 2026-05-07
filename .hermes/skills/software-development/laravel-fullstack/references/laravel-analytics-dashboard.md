# Laravel Analytics Dashboard Pattern

Reusable pattern for building a comprehensive analytics page in Laravel + Blade + Chart.js. Designed for trading journal apps but applicable to any time-series analytics.

## Architecture

One controller (`AnalyticsController`) → one view (`analytics/index.blade.php`). All data computed server-side in a single method. Chart.js CDN loaded via `@push('scripts')`.

## Controller Data Computation

All queries share a base `$query` filtered by `user_id` and optional `?account=` parameter. Clone with `(clone $query)` before each aggregate.

### Overview Stats
```php
$totalTrades = (clone $query)->count();
$totalWins = (clone $query)->where('result', 'win')->count();
$totalPnl = (clone $query)->sum('profit_loss');
$winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;
$avgDuration = (clone $query)->whereNotNull('duration_minutes')->avg('duration_minutes');
```

### Pair Performance (GROUP BY)
```php
$pairStats = (clone $query)
    ->selectRaw('currency_pair, COUNT(*) as trades, SUM(profit_loss) as total_pnl, AVG(profit_loss) as avg_pnl')
    ->groupBy('currency_pair')
    ->orderByDesc('total_pnl')
    ->get();
$bestPair = $pairStats->first();
$worstPair = $pairStats->where('total_pnl', '<', 0)->sortBy('total_pnl')->first();
```

### Equity Curve (Cumulative P&L)
```php
$equityData = (clone $query)
    ->whereNotNull('close_date')
    ->orderBy('close_date')
    ->get(['close_date', 'profit_loss']);

$cumulative = 0;
foreach ($equityData as $trade) {
    $cumulative += $trade->profit_loss;
    $equityDates[] = $trade->close_date->format('d M Y');
    $equityValues[] = round($cumulative, 2);
}
```

### Heatmap (Day × Hour)
```php
$heatmapRaw = (clone $query)
    ->whereNotNull('close_date')
    ->selectRaw('DAYOFWEEK(close_date) as dow, HOUR(close_date) as hour, COUNT(*) as trades, SUM(profit_loss) as pnl')
    ->groupBy('dow', 'hour')
    ->get();

// Build 7×24 matrix
for ($d = 1; $d <= 7; $d++) {
    for ($h = 0; $h < 24; $h++) {
        $cell = $heatmapRaw->firstWhere(fn($r) => (int)$r->dow === $d && (int)$r->hour === $h);
        $heatmap[$d][$h] = ['trades' => $cell ? (int)$cell->trades : 0, 'pnl' => $cell ? (float)$cell->pnl : 0];
    }
}
```

### Streak Tracker
```php
$tradesSorted = (clone $query)->orderBy('close_date')->pluck('result');
$maxWinStreak = $maxLossStreak = $currentWin = $currentLoss = 0;
foreach ($tradesSorted as $result) {
    if ($result === 'win') { $currentWin++; $currentLoss = 0; $maxWinStreak = max($maxWinStreak, $currentWin); }
    elseif ($result === 'loss') { $currentLoss++; $currentWin = 0; $maxLossStreak = max($maxLossStreak, $currentLoss); }
    else { $currentWin = $currentLoss = 0; }
}
```

### Risk Metrics
```php
$avgWin = (clone $query)->where('result', 'win')->avg('profit_loss') ?: 0;
$avgLoss = (clone $query)->where('result', 'loss')->avg('profit_loss') ?: 0;
$profitFactor = $avgLoss != 0 ? round(abs($avgWin / $avgLoss), 2) : 0;

// Max Drawdown from equity curve (per-trade granularity)
$maxDrawdown = 0; $peak = 0; $cumDD = 0;
foreach ($equityData as $trade) {
    $cumDD += $trade->profit_loss;
    if ($cumDD > $peak) $peak = $cumDD;
    $maxDrawdown = max($maxDrawdown, $peak - $cumDD);
}

// Daily Drawdown (per-day granularity with dates + daily P&L)
// Shows the 10 worst drawdown days with their date and daily P&L.
// IMPORTANT: "daily P&L" = profit/loss on that date alone (NOT cumulative equity).
// Users need to see what happened on that specific day, not the running total.
$dailyPnlRaw = (clone $query)
    ->whereNotNull('close_date')
    ->selectRaw('DATE(close_date) as date, SUM(profit_loss) as pnl')
    ->groupBy('date')
    ->orderBy('date')
    ->get();

// Build cumulative equity per day, then compute drawdown from high-water mark
$dailyCumPnl = [];
$cumPnl = 0;
foreach ($dailyPnlRaw as $row) {
    $cumPnl += $row->pnl;
    $dailyCumPnl[] = [
        'date' => $row->date,
        'cum' => $cumPnl,
        'daily_pnl' => round($row->pnl, 2),
    ];
}

$dailyDrawdownList = [];
$ddHighWater = 0;
foreach ($dailyCumPnl as $entry) {
    if ($entry['cum'] > $ddHighWater) {
        $ddHighWater = $entry['cum'];
    }
    $dd = $ddHighWater - $entry['cum'];
    $dailyDrawdownList[] = [
        'date' => $entry['date'],
        'drawdown' => round($dd, 2),
        'daily_pnl' => $entry['daily_pnl'],
    ];
}

// Sort by drawdown descending, take top 10 worst days
$dailyDrawdownList = collect($dailyDrawdownList)
    ->sortByDesc('drawdown')
    ->take(10)
    ->values()
    ->all();
```

**Domain terminology note:** In trading analytics, "equity" means cumulative P&L from all trades up to that point — NOT the daily profit/loss. When users ask for drawdown breakdown by date, they typically want to see the **daily P&L** (what happened on that specific day), not the running total. Always clarify which metric the user wants before building the table.

### Daily Drawdown Table (Blade)

```blade
@if(count($dailyDrawdownList) > 0)
<div class="card p-4 mt-4">
    <h3 class="text-sm font-semibold mb-3" style="color: var(--text-secondary);">
        <i class="fas fa-arrow-trend-down mr-1 text-red-400"></i>Daily Drawdown — 10 Hari Terburuk
    </h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr style="color: var(--text-secondary);" class="text-[10px] uppercase">
                    <th class="text-left py-1.5 pr-3 font-medium">#</th>
                    <th class="text-left py-1.5 pr-3 font-medium">Tanggal</th>
                    <th class="text-right py-1.5 pr-3 font-medium">P&L Harian</th>
                    <th class="text-right py-1.5 font-medium">Drawdown</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyDrawdownList as $i => $dd)
                <tr class="border-t" style="border-color: var(--border-color);">
                    <td class="py-1.5 pr-3" style="color: var(--text-secondary);">{{ $i + 1 }}</td>
                    <td class="py-1.5 pr-3 font-medium">{{ \Carbon\Carbon::parse($dd['date'])->format('d M Y') }}</td>
                    <td class="py-1.5 pr-3 text-right font-bold {{ $dd['daily_pnl'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $dd['daily_pnl'] >= 0 ? '+' : '' }}{{ number_format($dd['daily_pnl'], 2) }}
                    </td>
                    <td class="py-1.5 text-right font-bold text-red-400">-{{ number_format($dd['drawdown'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
```

## Chart.js Patterns

### Conditional Bar Colors
```js
const values = pairData.map(d => parseFloat(d.total_pnl));
const colors = values.map(v => v >= 0 ? 'rgba(52, 211, 153, 0.8)' : 'rgba(248, 113, 113, 0.8)');
```

### Responsive Point Radius & Ticks
```js
pointRadius: window.innerWidth < 640 ? 0 : 2,
x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: window.innerWidth < 640 ? 5 : 10 } }
```

### Horizontal Bars on Mobile
```js
indexAxis: window.innerWidth < 640 ? 'y' : 'x',
```

### Profit Factor Color Threshold
```blade
<div class="{{ $profitFactor >= 1.5 ? 'text-emerald-400' : ($profitFactor >= 1 ? 'text-yellow-400' : 'text-red-400') }}">
    {{ $profitFactor }}
</div>
```

## Heatmap HTML Pattern

```blade
<table class="w-full text-[10px]">
    <thead>
        <tr>
            <th class="py-1 px-1 text-left w-10"></th>
            @for($h = 0; $h < 24; $h++)
                <th class="py-1 px-0.5 text-center">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @for($d = 1; $d <= 7; $d++)
        <tr>
            <td class="py-1 px-1 font-medium">{{ $dayNames[$d - 1] }}</td>
            @for($h = 0; $h < 24; $h++)
                @php
                    $cell = $heatmap[$d][$h];
                    $intensity = $maxTrades > 0 ? $cell['trades'] / $maxTrades : 0;
                @endphp
                <td class="py-1 px-0.5">
                    <div class="rounded-sm w-full aspect-square flex items-center justify-center"
                         style="background-color: rgba(6, 182, 212, {{ $intensity * 0.85 + ($cell['trades'] > 0 ? 0.1 : 0) }});"
                         title="{{ $dayNames[$d-1] }} {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 — {{ $cell['trades'] }} trades, P&L: {{ number_format($cell['pnl'], 2) }}">
                        @if($cell['trades'] > 0)
                            <span class="text-white font-bold text-[8px]">{{ $cell['trades'] }}</span>
                        @endif
                    </div>
                </td>
            @endfor
        </tr>
        @endfor
    </tbody>
</table>
```

Wrap in `overflow-x-auto` + `min-w-[600px]` for mobile scroll support.
