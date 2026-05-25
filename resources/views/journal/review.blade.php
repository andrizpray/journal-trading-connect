@extends('layouts.app')
@section('page-title', 'Review')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-calendar-alt mr-2 text-cyan-400"></i>Trading Review
        </h1>
        <p class="text-sm mt-1" style="color: var(--text-secondary);">{{ $periodLabel }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('journal.review', ['period' => 'weekly']) }}" class="px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $period === 'weekly' ? 'btn-primary' : 'btn-secondary' }}">
            <i class="fas fa-calendar-week mr-1"></i>Mingguan
        </a>
        <a href="{{ route('journal.review', ['period' => 'monthly']) }}" class="px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $period === 'monthly' ? 'btn-primary' : 'btn-secondary' }}">
            <i class="fas fa-calendar mr-1"></i>Bulanan
        </a>
        <a href="{{ route('journal.index') }}" class="btn-secondary px-3 py-2 rounded-lg text-xs font-medium">
            <i class="fas fa-arrow-left mr-1"></i>Jurnal
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="card p-4 text-center">
        <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Total Trades</div>
        <div class="text-xl font-bold text-white mt-1">{{ number_format($totalTrades, 0, ',', '.') }}</div>
        <div class="text-[10px] mt-1" style="color: var(--text-secondary);">
            <span class="text-emerald-400">{{ $wins }}W</span> ·
            <span class="text-red-400">{{ $losses }}L</span>
        </div>
    </div>
    <div class="card p-4 text-center">
        <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Win Rate</div>
        <div class="text-xl font-bold mt-1 {{ $winRate >= 50 ? 'text-emerald-400' : 'text-red-400' }}">{{ $winRate }}%</div>
    </div>
    <div class="card p-4 text-center">
        <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Total P&L</div>
        <div class="text-xl font-bold mt-1 {{ $totalPnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $totalPnl >= 0 ? '+' : '' }}{{ number_format($totalPnl, 0, ',', '.') }}
        </div>
        @if($pnlChange !== null)
            <div class="text-[10px] mt-1 {{ $pnlChange >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                {{ $pnlChange >= 0 ? '↑' : '↓' }} {{ abs($pnlChange) }}% vs periode lalu
            </div>
        @endif
    </div>
    <div class="card p-4 text-center">
        <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Avg P&L / Trade</div>
        <div class="text-xl font-bold mt-1 {{ $avgPnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $avgPnl >= 0 ? '+' : '' }}{{ number_format($avgPnl, 2) }}
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Top Pairs --}}
    <div class="card p-4">
        <h3 class="text-sm font-semibold text-white mb-3">
            <i class="fas fa-trophy mr-2 text-yellow-400"></i>Top Pairs
        </h3>
        @if($topPairs->isNotEmpty())
            <div class="space-y-2">
                @foreach($topPairs as $pair)
                <div class="flex items-center justify-between py-1.5">
                    <div>
                        <span class="font-medium text-white text-sm">{{ $pair->currency_pair }}</span>
                        <span class="text-[10px] ml-2" style="color: var(--text-secondary);">{{ $pair->trades }} trades</span>
                    </div>
                    <span class="text-sm font-bold {{ $pair->pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $pair->pnl >= 0 ? '+' : '' }}{{ number_format($pair->pnl, 2) }}
                    </span>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-xs" style="color: var(--text-secondary);">Tidak ada trade di periode ini</p>
        @endif
    </div>

    {{-- Emotion + Lessons --}}
    <div class="card p-4">
        <h3 class="text-sm font-semibold text-white mb-3">
            <i class="fas fa-lightbulb mr-2 text-yellow-400"></i>Insight
        </h3>

        @if($avgEmotion !== null)
        <div class="mb-3 pb-3 border-b" style="border-color: var(--border-color);">
            <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Avg Emotion Score</div>
            <div class="flex items-center gap-1 mt-1">
                @for($i = 1; $i <= 5; $i++)
                    <span class="text-lg {{ $i <= round($avgEmotion) ? 'text-yellow-400' : 'text-gray-700' }}">★</span>
                @endfor
                <span class="text-sm font-bold text-white ml-2">{{ $avgEmotion }}/5</span>
            </div>
        </div>
        @endif

        @if($topLessons->isNotEmpty())
        <div>
            <div class="text-[10px] uppercase mb-2" style="color: var(--text-secondary);">Pelajaran Terbaru</div>
            <div class="space-y-2">
                @foreach($topLessons as $lesson)
                <div class="text-xs p-2 rounded-lg" style="background-color: var(--bg-secondary); color: var(--text-secondary);">
                    💡 {{ Str::limit($lesson, 120) }}
                </div>
                @endforeach
            </div>
        </div>
        @else
            <p class="text-xs" style="color: var(--text-secondary);">Tulis jurnal untuk melihat pelajaran di sini</p>
        @endif
    </div>
</div>

{{-- Empty state --}}
@if($totalTrades === 0 && $journals->isEmpty())
<div class="card p-12 text-center">
    <i class="fas fa-calendar-times text-3xl mb-3" style="color: var(--text-secondary);"></i>
    <h3 class="font-bold text-white mb-2">Belum Ada Aktivitas</h3>
    <p class="text-sm" style="color: var(--text-secondary);">Review akan muncul saat ada trade & jurnal di periode ini.</p>
</div>
@endif
@endsection
