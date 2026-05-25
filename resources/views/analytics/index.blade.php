@extends('layouts.app')
@section('page-title', 'Analytics')

@section('content')
{{-- Account Filter --}}
@if($accounts->isNotEmpty() && $accounts->count() > 1)
<div class="mb-4 flex flex-wrap items-center gap-3">
    <span class="text-xs" style="color: var(--text-secondary);"><i class="fas fa-filter mr-1"></i>Filter Akun:</span>
    <form method="GET" action="{{ route('analytics.index') }}" class="flex gap-2">
        <select name="account" onchange="this.form.submit()" class="dark-input text-xs py-1.5 px-3 w-auto">
            <option value="">Semua Akun</option>
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ $accountId == $acc->id ? 'selected' : '' }}>
                    {{ $acc->broker }} ({{ $acc->account_number }})
                </option>
            @endforeach
        </select>
        @if($accountId)
            <a href="{{ route('analytics.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300">Reset</a>
        @endif
    </form>
</div>
@endif

{{-- 2.1 — Overview Stats --}}
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-chart-pie mr-2 text-cyan-400"></i>Overview</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="card stat-card stat-card-cyan p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Total Trades</div>
            <div class="text-xl font-bold text-white mt-1">{{ number_format($totalTrades, 0, ',', '.') }}</div>
            <div class="text-[10px] mt-1" style="color: var(--text-secondary);">
                <span class="text-emerald-400">{{ $totalWins }}W</span> ·
                <span class="text-red-400">{{ $totalLosses }}L</span> ·
                <span class="text-yellow-400">{{ $totalBE }}BE</span>
            </div>
        </div>
        <div class="card stat-card stat-card-green p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Total P&L</div>
            <div class="text-xl font-bold mt-1 {{ $totalPnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                {{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl, 0, ',', '.') }}
            </div>
            <div class="text-[10px] mt-1" style="color: var(--text-secondary);">Avg: {{ $avgPnl >= 0 ? '+' : '' }}{{ number_format($avgPnl, 2) }}</div>
        </div>
        <div class="card stat-card stat-card-yellow p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Win Rate</div>
            <div class="text-xl font-bold mt-1 {{ $winRate >= 50 ? 'text-emerald-400' : 'text-red-400' }}">{{ $winRate }}%</div>
        </div>
        <div class="card stat-card stat-card-purple p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Avg Durasi</div>
            <div class="text-xl font-bold text-white mt-1">
                {{ $avgDuration ? ($avgDuration >= 60 ? floor($avgDuration / 60) . 'j ' . round($avgDuration % 60) . 'm' : round($avgDuration) . 'm') : '-' }}
            </div>
        </div>
    </div>
</div>

{{-- 2.2 — Pair Performance + Best/Worst --}}
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-chart-bar mr-2 text-purple-400"></i>Performa per Pair</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="card p-4 lg:col-span-2">
            <div class="h-[250px] sm:h-[300px]">
                <canvas id="pairChart"></canvas>
            </div>
        </div>
        <div class="space-y-3">
            @if($bestPair)
            <div class="card p-4">
                <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Best Pair</div>
                <div class="font-bold text-white mt-1">{{ $bestPair->currency_pair }}</div>
                <div class="text-sm font-bold text-emerald-400">+{{ number_format($bestPair->total_pnl, 2) }}</div>
                <div class="text-[10px] mt-1" style="color: var(--text-secondary);">{{ $bestPair->trades }} trades · avg {{ number_format($bestPair->avg_pnl, 2) }}</div>
            </div>
            @endif
            @if($worstPair)
            <div class="card p-4">
                <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Worst Pair</div>
                <div class="font-bold text-white mt-1">{{ $worstPair->currency_pair }}</div>
                <div class="text-sm font-bold text-red-400">{{ number_format($worstPair->total_pnl, 2) }}</div>
                <div class="text-[10px] mt-1" style="color: var(--text-secondary);">{{ $worstPair->trades }} trades · avg {{ number_format($worstPair->avg_pnl, 2) }}</div>
            </div>
            @endif
            @if(!$bestPair && !$worstPair)
            <div class="card p-4 text-center">
                <p class="text-xs" style="color: var(--text-secondary);">Belum ada data pair</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- 2.3 — Equity Curve --}}
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-chart-line mr-2 text-emerald-400"></i>Equity Curve</h2>
    <div class="card p-4">
        <div class="h-[250px] sm:h-[300px]">
            <canvas id="equityChart"></canvas>
        </div>
    </div>
</div>

{{-- 2.5 — Streak Tracker --}}
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-fire mr-2 text-orange-400"></i>Streak</h2>
    <div class="grid grid-cols-3 gap-3">
        <div class="card p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Max Win Streak</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ $maxWinStreak }}</div>
            <div class="text-[10px] mt-1" style="color: var(--text-secondary);">trade berturut</div>
        </div>
        <div class="card p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Max Loss Streak</div>
            <div class="text-2xl font-bold text-red-400 mt-1">{{ $maxLossStreak }}</div>
            <div class="text-[10px] mt-1" style="color: var(--text-secondary);">trade berturut</div>
        </div>
        <div class="card p-4 text-center">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Streak Sekarang</div>
            <div class="text-2xl font-bold mt-1 {{ $currentStreakType === 'win' ? 'text-emerald-400' : ($currentStreakType === 'loss' ? 'text-red-400' : 'text-gray-400') }}">
                {{ $currentStreak }}{{ $currentStreakType ? ($currentStreakType === 'win' ? 'W' : 'L') : '' }}
            </div>
            <div class="text-[10px] mt-1" style="color: var(--text-secondary);">{{ $currentStreak > 0 ? 'trade aktif' : 'tidak ada' }}</div>
        </div>
    </div>
</div>

{{-- 2.6 — Risk Metrics --}}
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-shield-alt mr-2 text-yellow-400"></i>Risk Metrics</h2>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Max Drawdown</div>
            <div class="text-lg font-bold text-red-400 mt-1">{{ number_format($maxDrawdown, 2) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Avg Win</div>
            <div class="text-lg font-bold text-emerald-400 mt-1">+{{ number_format($avgWin, 2) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Avg Loss</div>
            <div class="text-lg font-bold text-red-400 mt-1">{{ number_format($avgLoss, 2) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Profit Factor</div>
            <div class="text-lg font-bold mt-1 {{ $profitFactor >= 1.5 ? 'text-emerald-400' : ($profitFactor >= 1 ? 'text-yellow-400' : 'text-red-400') }}">{{ $profitFactor }}</div>
        </div>
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Risk:Reward</div>
            <div class="text-lg font-bold text-cyan-400 mt-1">1:{{ $rrRatio }}</div>
        </div>
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Largest Win</div>
            <div class="text-lg font-bold text-emerald-400 mt-1">+{{ number_format($maxWin, 2) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Largest Loss</div>
            <div class="text-lg font-bold text-red-400 mt-1">{{ number_format($maxLoss, 2) }}</div>
        </div>
    </div>

    {{-- Daily Drawdown Table --}}
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
</div>

{{-- 2.4 — Heatmap Trading --}}
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-th mr-2 text-cyan-400"></i>Heatmap Trading</h2>
    <div class="card p-4 overflow-x-auto">
        <div class="min-w-[600px]">
            <p class="text-[10px] mb-3" style="color: var(--text-secondary);">Frekuensi trade per hari & jam (warna = jumlah trade)</p>
            <table class="w-full text-[10px]">
                <thead>
                    <tr style="color: var(--text-secondary);">
                        <th class="py-1 px-1 text-left w-10"></th>
                        @for($h = 0; $h < 24; $h++)
                            <th class="py-1 px-0.5 text-center">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @for($d = 1; $d <= 7; $d++)
                    <tr>
                        <td class="py-1 px-1 font-medium" style="color: var(--text-secondary);">{{ $dayNames[$d - 1] }}</td>
                        @for($h = 0; $h < 24; $h++)
                            @php $cell = $heatmap[$d][$h]; $intensity = $maxTrades > 0 ? $cell['trades'] / $maxTrades : 0; @endphp
                            <td class="py-1 px-0.5">
                                <div class="heatmap-cell rounded-sm w-full aspect-square flex items-center justify-center cursor-default"
                                     style="background-color: rgba(6, 182, 212, {{ $intensity * 0.85 + ($cell['trades'] > 0 ? 0.1 : 0) }});"
                                     title="{{ $dayNames[$d - 1] }} {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 — {{ $cell['trades'] }} trades, P&L: {{ number_format($cell['pnl'], 2) }}">
                                    @if($cell['trades'] > 0)
                                        <span class="text-white font-bold">{{ $cell['trades'] }}</span>
                                    @endif
                                </div>
                            </td>
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- 2.7 — Account Comparison --}}
@if($accountComparison->count() > 1)
<div class="mb-6">
    <h2 class="text-lg font-bold text-white mb-3"><i class="fas fa-balance-scale mr-2 text-purple-400"></i>Perbandingan Akun</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <div class="card p-4">
            <div class="h-[200px] sm:h-[250px]">
                <canvas id="accountChart"></canvas>
            </div>
        </div>
        <div class="card p-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr style="color: var(--text-secondary);">
                        <th class="text-left py-2">Broker</th>
                        <th class="text-right py-2">Trades</th>
                        <th class="text-right py-2">Win Rate</th>
                        <th class="text-right py-2">P&L</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accountComparison as $acc)
                    <tr class="border-t" style="border-color: var(--border-color);">
                        <td class="py-2 font-medium text-white">{{ $acc['broker'] }} <span class="font-mono text-[10px]" style="color: var(--text-secondary);">({{ $acc['account_number'] }})</span></td>
                        <td class="py-2 text-right">{{ $acc['total_trades'] }}</td>
                        <td class="py-2 text-right {{ $acc['win_rate'] >= 50 ? 'text-emerald-400' : 'text-red-400' }}">{{ $acc['win_rate'] }}%</td>
                        <td class="py-2 text-right font-bold {{ $acc['total_pnl'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ $acc['total_pnl'] >= 0 ? '+' : '' }}{{ number_format($acc['total_pnl'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Empty state --}}
@if($totalTrades === 0)
<div class="card p-12 text-center mb-6">
    <div class="w-20 h-20 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background-color: var(--bg-secondary);">
        <i class="fas fa-chart-area text-3xl" style="color: var(--text-secondary);"></i>
    </div>
    <h3 class="font-bold text-lg text-white mb-2">Belum Ada Data</h3>
    <p class="text-sm mb-4 max-w-xs mx-auto" style="color: var(--text-secondary);">Import CSV untuk mulai melihat analisis performa trading Anda.</p>
    <a href="{{ route('import.index') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm">
        <i class="fas fa-file-import"></i>Import CSV
    </a>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.color = '#9ca3af';
    Chart.defaults.borderColor = 'rgba(75, 85, 99, 0.3)';

    // 2.2 — Pair Performance Bar Chart
    const pairCtx = document.getElementById('pairChart');
    if (pairCtx) {
        const pairData = @json($pairChartData);
        const labels = pairData.map(d => d.currency_pair);
        const values = pairData.map(d => parseFloat(d.total_pnl));
        const colors = values.map(v => v >= 0 ? 'rgba(52, 211, 153, 0.8)' : 'rgba(248, 113, 113, 0.8)');
        const borders = values.map(v => v >= 0 ? '#34d399' : '#f87171');

        new Chart(pairCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'P&L',
                    data: values,
                    backgroundColor: colors,
                    borderColor: borders,
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: 'rgba(75, 85, 99, 0.3)' } }
                }
            }
        });
    }

    // 2.3 — Equity Curve
    const eqCtx = document.getElementById('equityChart');
    if (eqCtx && @json($equityDates).length > 0) {
        new Chart(eqCtx, {
            type: 'line',
            data: {
                labels: @json($equityDates),
                datasets: [{
                    label: 'Equity',
                    data: @json($equityValues),
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52, 211, 153, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: window.innerWidth < 640 ? 0 : 2,
                    pointHoverRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: window.innerWidth < 640 ? 5 : 10 }
                    },
                    y: { grid: { color: 'rgba(75, 85, 99, 0.3)' } }
                }
            }
        });
    }

    // 2.7 — Account Comparison Chart
    const accCtx = document.getElementById('accountChart');
    if (accCtx) {
        const accData = @json($accountComparison);
        new Chart(accCtx, {
            type: 'bar',
            data: {
                labels: accData.map(a => a.broker),
                datasets: [{
                    label: 'P&L',
                    data: accData.map(a => parseFloat(a.total_pnl)),
                    backgroundColor: accData.map(a => parseFloat(a.total_pnl) >= 0 ? 'rgba(6, 182, 212, 0.8)' : 'rgba(248, 113, 113, 0.8)'),
                    borderColor: accData.map(a => parseFloat(a.total_pnl) >= 0 ? '#06b6d4' : '#f87171'),
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: window.innerWidth < 640 ? 'y' : 'x',
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(75, 85, 99, 0.3)' } },
                    y: { grid: { display: window.innerWidth >= 640, color: 'rgba(75, 85, 99, 0.3)' } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
