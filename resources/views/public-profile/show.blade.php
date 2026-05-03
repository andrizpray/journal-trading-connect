<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $user->name }} — Trading Profile</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#06b6d4">
    <link rel="apple-touch-icon" href="/pwa-icons/icon-192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg-primary: #030712; --bg-secondary: #111827; --bg-card: #1f2937;
            --border-color: #374151; --text-primary: #f9fafb; --text-secondary: #9ca3af;
            --accent-cyan: #06b6d4;
        }
        * { -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-primary); color: var(--text-primary); margin: 0; }
        .stat-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 1rem; padding: 1rem; text-align: center; }
    </style>
</head>
<body class="antialiased">
    <div class="max-w-3xl mx-auto px-4 py-6 sm:py-10 space-y-6">
        {{-- Header --}}
        <div class="text-center">
            <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-2xl font-bold text-white mb-3"
                style="background: linear-gradient(135deg, #06b6d4, #8b5cf6);">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-white">{{ $user->name }}</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1">Trading Profile</p>
        </div>

        {{-- Stats Grid --}}
        @if(in_array('total_trades', $visibleFields) || in_array('win_rate', $visibleFields) || in_array('total_pnl', $visibleFields))
        <div class="grid grid-cols-3 gap-3">
            @if(in_array('total_trades', $visibleFields))
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Total Trades</div>
                <div class="text-lg sm:text-2xl font-bold mt-1 text-white">{{ number_format($totalTrades) }}</div>
                <div class="text-[10px] text-gray-500">{{ $totalWins }}W / {{ $totalLosses }}L</div>
            </div>
            @endif
            @if(in_array('win_rate', $visibleFields))
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Win Rate</div>
                <div class="text-lg sm:text-2xl font-bold mt-1 {{ $winRate >= 50 ? 'text-emerald-400' : 'text-red-400' }}">{{ $winRate }}%</div>
            </div>
            @endif
            @if(in_array('total_pnl', $visibleFields))
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Total P&L</div>
                <div class="text-lg sm:text-2xl font-bold mt-1 {{ $totalPnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl, 2) }}
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Equity Curve --}}
        @if(in_array('equity_curve', $visibleFields) && count($equityDates) > 0)
        <div class="stat-card">
            <h2 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                <i class="fas fa-chart-line text-cyan-400"></i> Equity Curve
            </h2>
            <div class="h-[200px] sm:h-[280px]">
                <canvas id="equityChart"></canvas>
            </div>
        </div>
        @endif

        {{-- Risk Metrics --}}
        @if(in_array('risk_metrics', $visibleFields))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Profit Factor</div>
                <div class="text-lg font-bold mt-1 text-white">{{ $profitFactor }}</div>
            </div>
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Max Drawdown</div>
                <div class="text-lg font-bold mt-1 text-red-400">-{{ number_format($maxDrawdown, 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Avg Win</div>
                <div class="text-lg font-bold mt-1 text-emerald-400">+{{ number_format($avgWin, 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="text-gray-400 text-[10px] sm:text-xs">Avg Loss</div>
                <div class="text-lg font-bold mt-1 text-red-400">{{ number_format($avgLoss, 2) }}</div>
            </div>
        </div>
        @endif

        {{-- Pair Performance --}}
        @if(in_array('pair_performance', $visibleFields) && $pairStats->count() > 0)
        <div class="stat-card text-left">
            <h2 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                <i class="fas fa-table text-cyan-400"></i> Performa per Pair
            </h2>
            <div class="space-y-2">
                @foreach($pairStats as $pair)
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-sm text-white font-medium">{{ $pair->currency_pair }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">{{ $pair->trades }} trades</span>
                        <span class="text-sm font-bold {{ $pair->pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ $pair->pnl >= 0 ? '+' : '' }}{{ number_format($pair->pnl, 2) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent Trades --}}
        @if(count($recentTrades) > 0)
        <div class="stat-card text-left">
            <h2 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                <i class="fas fa-history text-cyan-400"></i> 20 Trade Terakhir
            </h2>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <div class="min-w-[500px] px-4 sm:px-0">
                    <table class="w-full text-xs sm:text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b" style="border-color: var(--border-color);">
                                <th class="pb-2">Tanggal</th>
                                <th class="pb-2">Pair</th>
                                <th class="pb-2 text-center">Tipe</th>
                                <th class="pb-2 text-right">Lot</th>
                                <th class="pb-2 text-right">P&L</th>
                                <th class="pb-2 text-center">Hasil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTrades as $trade)
                            <tr>
                                <td class="py-1.5 text-gray-300">{{ $trade->close_date?->format('d M Y') }}</td>
                                <td class="py-1.5 text-white">{{ $trade->currency_pair }}</td>
                                <td class="py-1.5 text-center">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ in_array($trade->trade_type, ['buy','buy_limit','buy_stop']) ? 'bg-emerald-900/50 text-emerald-400' : 'bg-red-900/50 text-red-400' }}">
                                        {{ strtoupper($trade->trade_type) }}
                                    </span>
                                </td>
                                <td class="py-1.5 text-right text-gray-300">{{ number_format($trade->lot_size, 2) }}</td>
                                <td class="py-1.5 text-right font-medium {{ $trade->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    {{ $trade->profit_loss >= 0 ? '+' : '' }}{{ number_format($trade->profit_loss, 2) }}
                                </td>
                                <td class="py-1.5 text-center">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $trade->result === 'win' ? 'bg-emerald-900/50 text-emerald-400' : ($trade->result === 'loss' ? 'bg-red-900/50 text-red-400' : 'bg-gray-700/50 text-gray-300') }}">
                                        {{ strtoupper($trade->result) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="text-center py-4">
            <p class="text-xs text-gray-600">Powered by Journal Trading Connect</p>
        </div>
    </div>

    @if(in_array('equity_curve', $visibleFields) && count($equityDates) > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('equityChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($equityDates),
                datasets: [{
                    label: 'Equity',
                    data: @json($equityValues),
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: window.innerWidth < 640 ? 0 : 2,
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#f9fafb',
                        bodyColor: '#9ca3af',
                        borderColor: '#374151',
                        borderWidth: 1,
                        callbacks: {
                            label: function(ctx) {
                                return (ctx.raw >= 0 ? '+' : '') + ctx.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#6b7280', maxRotation: 0, autoSkip: true, maxTicksLimit: window.innerWidth < 640 ? 5 : 10, font: { size: 10 } },
                        grid: { color: 'rgba(55,65,81,0.3)' }
                    },
                    y: {
                        ticks: { color: '#6b7280', font: { size: 10 }, callback: v => (v >= 0 ? '+' : '') + v.toFixed(0) },
                        grid: { color: 'rgba(55,65,81,0.3)' }
                    }
                }
            }
        });
    });
    </script>
    @endif
</body>
</html>
