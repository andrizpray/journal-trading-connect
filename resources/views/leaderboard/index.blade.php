@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-trophy text-amber-400"></i> Leaderboard
            </h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1">Ranking performa trader</p>
        </div>
        {{-- Opt-in toggle --}}
        <form method="POST" action="{{ route('leaderboard.toggle') }}" class="flex-shrink-0">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                {{ Auth::user()->leaderboard_opt_in
                    ? 'bg-emerald-900/50 border border-emerald-600/50 text-emerald-400 hover:bg-emerald-900/70'
                    : 'bg-gray-800 border border-gray-700 text-gray-300 hover:bg-gray-700' }}">
                @if(Auth::user()->leaderboard_opt_in)
                    <i class="fas fa-check-circle mr-1"></i> Joined
                @else
                    <i class="fas fa-plus-circle mr-1"></i> Join Leaderboard
                @endif
            </button>
        </form>
    </div>

    {{-- My Rank Card --}}
    @if($myStats)
        <div class="p-4 sm:p-5 rounded-xl bg-gradient-to-r from-amber-900/30 to-gray-800/50 border border-amber-700/30">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="w-12 h-12 rounded-full bg-amber-900/50 flex items-center justify-center text-amber-400 text-xl font-bold">
                    #{{ $myRank }}
                </div>
                <div class="flex-1 min-w-[150px]">
                    <div class="text-sm font-medium text-white">Posisi Anda</div>
                    <div class="text-xs text-gray-400">
                        {{ $myStats['total_trades'] }} trades ·
                        {{ $myStats['win_rate'] }}% win rate ·
                        <span class="{{ $myStats['total_pnl'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ $myStats['total_pnl'] >= 0 ? '+' : '' }}{{ number_format($myStats['total_pnl'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        {{-- Period filter --}}
        <div class="flex rounded-lg overflow-hidden border border-gray-700/50">
            @foreach(['all_time' => 'All Time', 'yearly' => 'Tahun Ini', 'quarterly' => '3 Bulan', 'monthly' => 'Bulan Ini', 'weekly' => 'Minggu Ini'] as $key => $label)
                <a href="{{ route('leaderboard.index', ['period' => $key, 'sort' => $sortBy]) }}"
                    class="px-3 py-1.5 text-xs font-medium transition-colors
                    {{ $period === $key
                        ? 'bg-cyan-600 text-white'
                        : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Sort filter --}}
        <select onchange="window.location.href = '{{ route('leaderboard.index', ['period' => $period]) }}&sort=' + this.value"
            class="dark-input px-3 py-1.5 rounded-lg text-xs min-w-[130px]">
            <option value="win_rate" {{ $sortBy === 'win_rate' ? 'selected' : '' }}>Win Rate</option>
            <option value="total_pnl" {{ $sortBy === 'total_pnl' ? 'selected' : '' }}>Total P&L</option>
            <option value="total_trades" {{ $sortBy === 'total_trades' ? 'selected' : '' }}>Total Trades</option>
            <option value="avg_pnl" {{ $sortBy === 'avg_pnl' ? 'selected' : '' }}>Avg P&L / Trade</option>
        </select>
    </div>

    {{-- Top 3 Podium --}}
    @if($leaderboard->count() >= 3)
        <div class="grid grid-cols-3 gap-3 sm:gap-4">
            {{-- 2nd Place --}}
            <div class="order-1 sm:order-1 text-center pt-6 sm:pt-8">
                <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto rounded-full bg-gray-600/30 flex items-center justify-center text-gray-300 text-lg sm:text-2xl font-bold border-2 border-gray-500/50">
                    {{ strtoupper(substr($leaderboard[1]['name'], 0, 1)) }}
                </div>
                <div class="text-xs sm:text-sm font-medium text-white mt-2 truncate px-1">{{ $leaderboard[1]['name'] }}</div>
                <div class="text-[10px] sm:text-xs text-gray-400">{{ $leaderboard[1]['win_rate'] }}%</div>
                <div class="text-xs sm:text-sm font-bold text-emerald-400 mt-0.5">+{{ number_format($leaderboard[1]['total_pnl'], 2) }}</div>
                <div class="text-3xl sm:text-4xl font-black text-gray-600/50 mt-1">2</div>
            </div>

            {{-- 1st Place --}}
            <div class="order-0 sm:order-0 text-center">
                <div class="text-amber-400 text-xs sm:text-sm mb-2"><i class="fas fa-crown"></i></div>
                <div class="w-14 h-14 sm:w-20 sm:h-20 mx-auto rounded-full bg-amber-900/30 flex items-center justify-center text-amber-400 text-xl sm:text-3xl font-bold border-2 border-amber-500/50">
                    {{ strtoupper(substr($leaderboard[0]['name'], 0, 1)) }}
                </div>
                <div class="text-xs sm:text-sm font-bold text-white mt-2 truncate px-1">{{ $leaderboard[0]['name'] }}</div>
                <div class="text-[10px] sm:text-xs text-amber-400">{{ $leaderboard[0]['win_rate'] }}% WR · {{ $leaderboard[0]['total_trades'] }} trades</div>
                <div class="text-xs sm:text-sm font-bold text-emerald-400 mt-0.5">+{{ number_format($leaderboard[0]['total_pnl'], 2) }}</div>
                <div class="text-4xl sm:text-5xl font-black text-amber-500/60 mt-1">1</div>
            </div>

            {{-- 3rd Place --}}
            <div class="order-2 sm:order-2 text-center pt-8 sm:pt-10">
                <div class="w-11 h-11 sm:w-14 sm:h-14 mx-auto rounded-full bg-orange-900/20 flex items-center justify-center text-orange-400 text-base sm:text-xl font-bold border-2 border-orange-700/30">
                    {{ strtoupper(substr($leaderboard[2]['name'], 0, 1)) }}
                </div>
                <div class="text-xs sm:text-sm font-medium text-white mt-2 truncate px-1">{{ $leaderboard[2]['name'] }}</div>
                <div class="text-[10px] sm:text-xs text-gray-400">{{ $leaderboard[2]['win_rate'] }}%</div>
                <div class="text-xs sm:text-sm font-bold text-emerald-400 mt-0.5">+{{ number_format($leaderboard[2]['total_pnl'], 2) }}</div>
                <div class="text-3xl sm:text-4xl font-black text-orange-800/50 mt-1">3</div>
            </div>
        </div>
    @endif

    {{-- Full Leaderboard Table --}}
    <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs sm:text-sm">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-900/30">
                        <th class="px-4 py-3 text-center w-16">#</th>
                        <th class="px-4 py-3">Trader</th>
                        <th class="px-4 py-3 text-center">Win Rate</th>
                        <th class="px-4 py-3 text-center">W/L</th>
                        <th class="px-4 py-3 text-center">Trades</th>
                        <th class="px-4 py-3 text-right">Total P&L</th>
                        <th class="px-4 py-3 text-right hidden sm:table-cell">Avg / Trade</th>
                        <th class="px-4 py-3 text-center hidden md:table-cell">PF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/30">
                    @forelse($leaderboard as $entry)
                        <tr class="hover:bg-gray-700/20 transition-colors {{ $myStats && $entry['id'] === $myStats['id'] ? 'bg-cyan-900/10' : '' }}">
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold {{ $entry['rank'] <= 3 ? 'text-amber-400' : 'text-gray-400' }}">
                                    {{ $entry['rank'] <= 3 ? ['🥇','🥈','🥉'][$entry['rank']-1] : $entry['rank'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                                        {{ $entry['rank'] === 1 ? 'bg-amber-900/50 text-amber-400' : ($entry['rank'] === 2 ? 'bg-gray-600/50 text-gray-300' : ($entry['rank'] === 3 ? 'bg-orange-900/50 text-orange-400' : 'bg-gray-700/50 text-gray-400')) }}">
                                        {{ strtoupper(substr($entry['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-white">{{ $entry['name'] }}</span>
                                        @if($myStats && $entry['id'] === $myStats['id'])
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-cyan-900/50 text-cyan-400 ml-1">Anda</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold {{ $entry['win_rate'] >= 60 ? 'text-emerald-400' : ($entry['win_rate'] >= 40 ? 'text-amber-400' : 'text-red-400') }}">
                                    {{ $entry['win_rate'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-emerald-400">{{ $entry['wins_count'] }}</span>
                                <span class="text-gray-600">/</span>
                                <span class="text-red-400">{{ $entry['loss_count'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-300">{{ $entry['total_trades'] }}</td>
                            <td class="px-4 py-3 text-right font-medium whitespace-nowrap {{ $entry['total_pnl'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $entry['total_pnl'] >= 0 ? '+' : '' }}{{ number_format($entry['total_pnl'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right hidden sm:table-cell {{ $entry['avg_pnl'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $entry['avg_pnl'] >= 0 ? '+' : '' }}{{ number_format($entry['avg_pnl'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-center hidden md:table-cell text-gray-300">{{ $entry['profit_factor'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <i class="fas fa-users-slash text-3xl text-gray-600 mb-3 block"></i>
                                <p class="text-gray-400 text-sm">Belum ada trader di leaderboard</p>
                                <p class="text-gray-500 text-xs mt-1">Join leaderboard untuk mulai ranking!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Info --}}
    @if(!Auth::user()->leaderboard_opt_in)
        <div class="p-4 rounded-xl bg-cyan-900/20 border border-cyan-700/30 text-sm">
            <p class="text-cyan-300 flex items-start gap-2">
                <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
                <span>Leaderboard bersifat <strong>opt-in</strong>. Data Anda (nama, win rate, total trade, P&L) akan ditampilkan secara publik setelah Anda bergabung. Anda bisa keluar kapan saja.</span>
            </p>
        </div>
    @endif
</div>
@endsection
