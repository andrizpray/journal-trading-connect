@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white">Admin Dashboard</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1">Overview semua data sistem</p>
        </div>
        <a href="{{ route('admin.users') }}" class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-users"></i> Kelola User
        </a>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Total User</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ $totalUsers }}</div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Total Trade</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ number_format($totalTrades) }}</div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Total P&L</div>
            <div class="text-2xl font-bold mt-1 {{ $totalPnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                {{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl, 2) }}
            </div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Akun Trading</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ $totalAccounts }}</div>
        </div>
        <div class="stat-card text-center flex flex-col justify-center p-4 rounded-xl bg-gray-800/50 border border-gray-700/50">
            <div class="text-gray-400 text-xs">Journal</div>
            <div class="text-2xl font-bold mt-1 text-white">{{ number_format($totalJournals) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Users --}}
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-user-plus text-cyan-400"></i> User Terbaru
            </h2>
            @if($recentUsers->count() > 0)
                <div class="space-y-3">
                    @foreach($recentUsers as $u)
                        <a href="{{ route('admin.user-detail', $u->id) }}" class="flex items-center justify-between p-3 rounded-lg bg-gray-900/50 hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-cyan-900/50 flex items-center justify-center text-cyan-400 text-sm font-bold">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $u->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $u->email }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $u->role === 'admin' ? 'bg-amber-900/50 text-amber-400' : 'bg-gray-700/50 text-gray-300' }}">
                                    {{ ucfirst($u->role) }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $u->created_at->diffForHumans() }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">Belum ada user</p>
            @endif
        </div>

        {{-- Top Users by P&L --}}
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-trophy text-amber-400"></i> Top User (P&L)
            </h2>
            @if($topUsers->count() > 0)
                <div class="space-y-3">
                    @foreach($topUsers as $i => $u)
                        <a href="{{ route('admin.user-detail', $u->id) }}" class="flex items-center justify-between p-3 rounded-lg bg-gray-900/50 hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $i === 0 ? 'bg-amber-900/50 text-amber-400' : ($i === 1 ? 'bg-gray-600/50 text-gray-300' : ($i === 2 ? 'bg-orange-900/50 text-orange-400' : 'bg-gray-800/50 text-gray-400')) }}">
                                    {{ $i + 1 }}
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $u->name }}</div>
                                    <div class="text-xs text-gray-400">{{ number_format($u->trade_histories_count) }} trades</div>
                                </div>
                            </div>
                            <div class="text-sm font-bold {{ $u->total_pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $u->total_pnl >= 0 ? '+' : '' }}{{ number_format($u->total_pnl, 2) }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">Belum ada data trade</p>
            @endif
        </div>
    </div>

    {{-- Recent Imports --}}
    <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
        <h2 class="text-sm font-semibold text-gray-300 mb-4 flex items-center gap-2">
            <i class="fas fa-file-import text-cyan-400"></i> Import Terbaru
        </h2>
        @if($recentImports->count() > 0)
            <div class="overflow-x-auto -mx-3 sm:mx-0">
                <div class="min-w-[500px] sm:min-w-0 px-3 sm:px-0">
                    <table class="w-full text-xs sm:text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-700/50">
                                <th class="pb-2">User</th>
                                <th class="pb-2">Akun</th>
                                <th class="pb-2">File</th>
                                <th class="pb-2 text-center">Imported</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/30">
                            @foreach($recentImports as $log)
                                <tr>
                                    <td class="py-2 text-white">{{ $log->user->name ?? '-' }}</td>
                                    <td class="py-2 text-gray-300">{{ $log->tradingAccount->broker ?? '-' }}</td>
                                    <td class="py-2 text-gray-300">{{ $log->filename }}</td>
                                    <td class="py-2 text-center text-white">{{ $log->imported_count }}</td>
                                    <td class="py-2">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full
                                            {{ $log->status === 'completed' ? 'bg-emerald-900/50 text-emerald-400' : 'bg-red-900/50 text-red-400' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="text-gray-500 text-sm text-center py-4">Belum ada import</p>
        @endif
    </div>
</div>
@endsection
