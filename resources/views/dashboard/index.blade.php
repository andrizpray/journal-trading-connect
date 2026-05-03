@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 items-stretch">
    <div class="card stat-card stat-card-cyan p-4 sm:p-5 flex flex-col justify-center text-center">
        <div class="text-xs font-medium" style="color: var(--text-secondary);">
            <i class="fas fa-wallet mr-1"></i>Total Akun
        </div>
        <div class="text-2xl font-bold mt-1 text-white">{{ $totalAccounts }}</div>
    </div>
    <div class="card stat-card stat-card-purple p-4 sm:p-5 flex flex-col justify-center text-center">
        <div class="text-xs font-medium" style="color: var(--text-secondary);">
            <i class="fas fa-exchange-alt mr-1"></i>Total Trade
        </div>
        <div class="text-2xl font-bold mt-1 text-white">{{ number_format($totalTrades, 0, ',', '.') }}</div>
    </div>
    <div class="card stat-card stat-card-green p-4 sm:p-5 flex flex-col justify-center text-center">
        <div class="text-xs font-medium" style="color: var(--text-secondary);">
            <i class="fas fa-dollar-sign mr-1"></i>Total P&L
        </div>
        <div class="text-2xl font-bold mt-1 {{ $totalPnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl, 0, ',', '.') }}
        </div>
    </div>
    <div class="card stat-card stat-card-yellow p-4 sm:p-5 flex flex-col justify-center text-center">
        <div class="text-xs font-medium" style="color: var(--text-secondary);">
            <i class="fas fa-trophy mr-1"></i>Win Rate
        </div>
        <div class="text-2xl font-bold mt-1 {{ $winRate >= 50 ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $winRate }}%
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 mb-6">
    <div class="card p-4 sm:p-5 lg:col-span-2">
        <h3 class="text-sm font-semibold text-white mb-4">
            <i class="fas fa-chart-area mr-2 text-cyan-400"></i>P&L 30 Hari Terakhir
        </h3>
        <div class="h-[200px] sm:h-[280px]">
            <canvas id="pnlChart"></canvas>
        </div>
    </div>
    <div class="card p-4 sm:p-5">
        <h3 class="text-sm font-semibold text-white mb-4">
            <i class="fas fa-bolt mr-2 text-yellow-400"></i>Aksi Cepat
        </h3>
        <div class="space-y-3">
            <a href="{{ route('import.index') }}" class="btn-primary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
                <i class="fas fa-file-import"></i>Import CSV
            </a>
            <a href="{{ route('trading-accounts.index') }}" class="btn-secondary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
                <i class="fas fa-plus"></i>Tambah Akun
            </a>
            <a href="{{ route('journal.index') }}" class="btn-secondary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
                <i class="fas fa-book-open"></i>Buka Jurnal
            </a>
        </div>
    </div>
</div>

<div class="card p-4 sm:p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-white">
            <i class="fas fa-history mr-2 text-purple-400"></i>Trade Terakhir
        </h3>
        <a href="{{ route('trade-history.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition-colors">
            Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    @if($recentTrades->isEmpty())
        <div class="text-center py-12">
            <div class="w-16 h-16 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background-color: var(--bg-secondary);">
                <i class="fas fa-inbox text-2xl" style="color: var(--text-secondary);"></i>
            </div>
            <p class="text-sm" style="color: var(--text-secondary);">Belum ada trade. Import CSV untuk memulai!</p>
            <a href="{{ route('import.index') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm mt-3">
                <i class="fas fa-file-import"></i>Import CSV
            </a>
        </div>
    @else
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <div class="min-w-[480px] sm:min-w-0 px-4 sm:px-0">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr style="color: var(--text-secondary);">
                            <th class="text-left py-2 font-medium">Tanggal</th>
                            <th class="text-left py-2 font-medium">Pair</th>
                            <th class="text-left py-2 font-medium">Tipe</th>
                            <th class="text-right py-2 font-medium">Lot</th>
                            <th class="text-right py-2 font-medium w-24 sm:w-auto">P&L</th>
                            <th class="text-left py-2 font-medium hidden sm:table-cell">Akun</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTrades as $trade)
                        <tr class="table-row-hover border-t" style="border-color: var(--border-color);">
                            <td class="py-2.5 font-mono" style="color: var(--text-secondary);">{{ $trade->close_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-2.5 font-semibold text-white">{{ $trade->currency_pair }}</td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ str_starts_with($trade->trade_type, 'buy') ? 'bg-emerald-900/30 text-emerald-400' : 'bg-red-900/30 text-red-400' }}">
                                    {{ strtoupper($trade->trade_type) }}
                                </span>
                            </td>
                            <td class="py-2.5 text-right text-white">{{ number_format($trade->lot_size, 2) }}</td>
                            <td class="py-2.5 text-right font-bold whitespace-nowrap {{ $trade->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $trade->profit_loss >= 0 ? '+' : '' }}{{ currency_symbol($trade->tradingAccount?->currency) }}{{ number_format($trade->profit_loss, 2, ',', '.') }}
                            </td>
                            <td class="hidden sm:table-cell py-2.5" style="color: var(--text-secondary);">
                                {{ $trade->tradingAccount?->broker ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('pnlChart');
    if (!ctx) return;
    Chart.defaults.color = '#9ca3af';
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($dates),
            datasets: [{
                label: 'P&L',
                data: @json($pnlValues),
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: window.innerWidth < 640 ? 0 : 3,
                pointHoverRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(75, 85, 99, 0.3)' }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: window.innerWidth < 640 ? 5 : 10 } },
                y: { grid: { color: 'rgba(75, 85, 99, 0.3)' } }
            }
        }
    });
});
</script>
@endpush
@endsection
