@extends('layouts.app')
@section('page-title', 'Riwayat Import')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-clock-rotate-left mr-2 text-cyan-400"></i>Riwayat Import
        </h1>
        <p class="text-sm mt-1" style="color: var(--text-secondary);">Log semua import CSV</p>
    </div>
    <a href="{{ route('import.index') }}" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium shrink-0">
        <i class="fas fa-file-import"></i>Import Baru
    </a>
</div>

@if($logs->isEmpty())
    <div class="card p-12 text-center">
        <div class="w-16 h-16 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background-color: var(--bg-secondary);">
            <i class="fas fa-inbox text-2xl" style="color: var(--text-secondary);"></i>
        </div>
        <p class="text-sm" style="color: var(--text-secondary);">Belum ada riwayat import.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($logs as $log)
        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-white text-sm truncate max-w-[200px]">{{ $log->filename }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $log->status === 'completed' ? 'bg-emerald-900/30 text-emerald-400' : ($log->status === 'partial' ? 'bg-yellow-900/30 text-yellow-400' : 'bg-red-900/30 text-red-400') }}">
                            {{ strtoupper($log->status) }}
                        </span>
                    </div>
                    <p class="text-xs mt-1" style="color: var(--text-secondary);">
                        <i class="fas fa-server mr-1"></i>{{ $log->tradingAccount?->broker ?? '-' }} ({{ $log->tradingAccount?->account_number ?? '-' }})
                    </p>
                    <div class="flex flex-wrap gap-4 mt-3 text-xs">
                        <span style="color: var(--text-secondary);">
                            <i class="fas fa-file mr-1"></i>{{ $log->total_rows }} baris
                        </span>
                        <span class="text-emerald-400">
                            <i class="fas fa-check mr-1"></i>{{ $log->imported_count }} imported
                        </span>
                        <span class="text-yellow-400">
                            <i class="fas fa-forward mr-1"></i>{{ $log->skipped_count }} skip
                        </span>
                        @if($log->error_count > 0)
                        <span class="text-red-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $log->error_count }} error
                        </span>
                        @endif
                    </div>
                    @if($log->notes)
                        <p class="text-xs mt-2 text-gray-400 truncate" title="{{ $log->notes }}">{{ $log->notes }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <div class="text-[10px]" style="color: var(--text-secondary);">{{ $log->created_at->format('d M Y, H:i') }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 flex justify-center">
        {{ $logs->links('vendor.pagination.tailwind') }}
    </div>
@endif
@endsection
