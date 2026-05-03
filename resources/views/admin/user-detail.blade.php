@extends('layouts.app')

@section('title', 'Detail User — ' . $user->name)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-cyan-900/50 flex items-center justify-center text-cyan-400 text-lg font-bold">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white">{{ $user->name }}</h1>
                <p class="text-gray-400 text-xs sm:text-sm">{{ $user->email }}
                    <span class="text-[10px] px-2 py-0.5 rounded-full ml-2 {{ $user->role === 'admin' ? 'bg-amber-900/50 text-amber-400' : 'bg-gray-700/50 text-gray-300' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </p>
            </div>
        </div>
        <a href="{{ route('admin.users') }}" class="text-gray-400 hover:text-white text-sm flex items-center gap-2 transition-colors">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- User Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4">
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Akun Trading</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ $user->trading_accounts_count }}</div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Total Trades</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ number_format($user->trade_histories_count) }}</div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Win Rate</div>
            <div class="text-2xl font-bold mt-1 text-cyan-400">{{ $winRate }}%</div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Total P&L</div>
            <div class="text-2xl font-bold mt-1 {{ $user->total_pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                {{ $user->total_pnl >= 0 ? '+' : '' }}{{ number_format($user->total_pnl, 2) }}
            </div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Journal</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ $user->journal_entries_count }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Trading Accounts --}}
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-wallet text-cyan-400"></i> Akun Trading
            </h2>
            @if($accounts->count() > 0)
                <div class="space-y-3">
                    @foreach($accounts as $acc)
                        <div class="p-3 rounded-lg bg-gray-900/50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-white">{{ $acc->broker }} ({{ $acc->account_number }})</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-700/50 text-gray-300">{{ strtoupper($acc->platform) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-400">
                                <span>{{ number_format($acc->trade_histories_count) }} trades</span>
                                <span class="font-medium {{ $acc->total_pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                    P&L: {{ $acc->total_pnl >= 0 ? '+' : '' }}{{ number_format($acc->total_pnl, 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">Belum ada akun trading</p>
            @endif
        </div>

        {{-- Recent Journals --}}
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-book text-cyan-400"></i> Journal Terbaru
            </h2>
            @if($recentJournals->count() > 0)
                <div class="space-y-3">
                    @foreach($recentJournals as $j)
                        <div class="p-3 rounded-lg bg-gray-900/50">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-white">{{ $j->currency_pair }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full
                                    {{ $j->result === 'win' ? 'bg-emerald-900/50 text-emerald-400' : ($j->result === 'loss' ? 'bg-red-900/50 text-red-400' : 'bg-gray-700/50 text-gray-300') }}">
                                    {{ strtoupper($j->result) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 line-clamp-2">{{ Str::limit(strip_tags($j->analysis ?? $j->lesson_learned ?? '-'), 100) }}</p>
                            <div class="text-[10px] text-gray-500 mt-1">{{ $j->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">Belum ada journal</p>
            @endif
        </div>
    </div>

    {{-- Recent Trades --}}
    <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
        <h2 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
            <i class="fas fa-history text-cyan-400"></i> 20 Trade Terakhir
        </h2>
        @if($recentTrades->count() > 0)
            <div class="overflow-x-auto -mx-3 sm:mx-0">
                <div class="min-w-[600px] sm:min-w-0 px-3 sm:px-0">
                    <table class="w-full text-xs sm:text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-700/50">
                                <th class="pb-2">Tanggal</th>
                                <th class="pb-2">Pair</th>
                                <th class="pb-2 text-center">Tipe</th>
                                <th class="pb-2 text-right">Lot</th>
                                <th class="pb-2 text-right">P&L</th>
                                <th class="pb-2 text-center hidden sm:table-cell">Hasil</th>
                                <th class="pb-2 hidden sm:table-cell">Akun</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/30">
                            @foreach($recentTrades as $trade)
                                <tr>
                                    <td class="py-2 text-gray-300 whitespace-nowrap">{{ $trade->close_date?->format('d M Y') }}</td>
                                    <td class="py-2 text-white font-medium">{{ $trade->currency_pair }}</td>
                                    <td class="py-2 text-center">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ in_array($trade->trade_type, ['buy', 'buy_limit', 'buy_stop']) ? 'bg-emerald-900/50 text-emerald-400' : 'bg-red-900/50 text-red-400' }}">
                                            {{ strtoupper($trade->trade_type) }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-right text-gray-300">{{ number_format($trade->lot_size, 2) }}</td>
                                    <td class="py-2 text-right font-medium whitespace-nowrap {{ $trade->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                        {{ $trade->profit_loss >= 0 ? '+' : '' }}{{ number_format($trade->profit_loss, 2) }}
                                    </td>
                                    <td class="py-2 text-center hidden sm:table-cell">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full
                                            {{ $trade->result === 'win' ? 'bg-emerald-900/50 text-emerald-400' : ($trade->result === 'loss' ? 'bg-red-900/50 text-red-400' : 'bg-gray-700/50 text-gray-300') }}">
                                            {{ strtoupper($trade->result) }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-gray-400 hidden sm:table-cell">{{ $trade->tradingAccount->broker ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="text-gray-500 text-sm text-center py-4">Belum ada trade</p>
        @endif
    </div>
</div>
@endsection
