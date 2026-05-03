@extends('layouts.app')
@section('page-title', 'Riwayat Trade')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-history mr-2 text-cyan-400"></i>Riwayat Trade
        </h1>
        <p class="text-sm mt-1" style="color: var(--text-secondary);">{{ $trades->total() }} trade ditemukan</p>
    </div>
    <a href="{{ route('import.index') }}" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium shrink-0">
        <i class="fas fa-file-import"></i>Import Baru
    </a>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('trade-history.index') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[140px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Akun</label>
            <select name="account_id" class="dark-input w-full text-sm py-2">
                <option value="">Semua Akun</option>
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->broker }} ({{ $acc->account_number }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[120px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Pair</label>
            <select name="pair" class="dark-input w-full text-sm py-2">
                <option value="">Semua Pair</option>
                @foreach($pairs as $pair)
                <option value="{{ $pair }}" {{ request('pair') == $pair ? 'selected' : '' }}>{{ $pair }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[120px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Hasil</label>
            <select name="result" class="dark-input w-full text-sm py-2">
                <option value="">Semua</option>
                <option value="win" {{ request('result') === 'win' ? 'selected' : '' }}>Win</option>
                <option value="loss" {{ request('result') === 'loss' ? 'selected' : '' }}>Loss</option>
                <option value="break_even" {{ request('result') === 'break_even' ? 'selected' : '' }}>Break Even</option>
            </select>
        </div>
        <div class="flex-1 min-w-[130px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="dark-input w-full text-sm py-2">
        </div>
        <div class="flex-1 min-w-[130px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="dark-input w-full text-sm py-2">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary px-3 py-2 rounded-lg text-sm">
                <i class="fas fa-filter"></i>
            </button>
            <a href="{{ route('trade-history.index') }}" class="btn-secondary px-3 py-2 rounded-lg text-sm">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card p-4 sm:p-6">
    @if($trades->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-search text-3xl mb-3" style="color: var(--text-secondary);"></i>
            <p class="text-sm" style="color: var(--text-secondary);">Tidak ada trade ditemukan.</p>
        </div>
    @else
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <div class="min-w-[700px] sm:min-w-0 px-4 sm:px-0">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr style="color: var(--text-secondary);">
                            <th class="text-left py-2 font-medium">Tanggal</th>
                            <th class="text-left py-2 font-medium">Pair</th>
                            <th class="text-left py-2 font-medium">Tipe</th>
                            <th class="text-right py-2 font-medium">Lot</th>
                            <th class="text-right py-2 font-medium">Open</th>
                            <th class="text-right py-2 font-medium">Close</th>
                            <th class="text-right py-2 font-medium">P&L</th>
                            <th class="text-center py-2 font-medium">Hasil</th>
                            <th class="text-left py-2 font-medium">Durasi</th>
                            <th class="hidden sm:table-cell text-left py-2 font-medium">Akun</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trades as $trade)
                        <tr class="table-row-hover border-t" style="border-color: var(--border-color);">
                            <td class="py-2.5 font-mono" style="color: var(--text-secondary);">{{ $trade->close_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-2.5 font-semibold text-white">{{ $trade->currency_pair }}</td>
                            <td class="py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] sm:text-xs font-medium {{ str_starts_with($trade->trade_type, 'buy') ? 'bg-emerald-900/30 text-emerald-400' : 'bg-red-900/30 text-red-400' }}">
                                    {{ strtoupper($trade->trade_type) }}
                                </span>
                            </td>
                            <td class="py-2.5 text-right text-white">{{ number_format($trade->lot_size, 2) }}</td>
                            <td class="py-2.5 text-right font-mono text-white">{{ $trade->open_price ? number_format($trade->open_price, 5) : '-' }}</td>
                            <td class="py-2.5 text-right font-mono text-white">{{ $trade->close_price ? number_format($trade->close_price, 5) : '-' }}</td>
                            <td class="py-2.5 text-right font-bold {{ $trade->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $trade->profit_loss >= 0 ? '+' : '' }}{{ number_format($trade->profit_loss, 2, ',', '.') }}
                            </td>
                            <td class="py-2.5 text-center">
                                @if($trade->result === 'win')
                                    <span class="text-emerald-400 font-medium text-[10px] sm:text-xs">WIN</span>
                                @elseif($trade->result === 'loss')
                                    <span class="text-red-400 font-medium text-[10px] sm:text-xs">LOSS</span>
                                @else
                                    <span class="text-yellow-400 font-medium text-[10px] sm:text-xs">BE</span>
                                @endif
                            </td>
                            <td class="py-2.5" style="color: var(--text-secondary);">
                                @if($trade->duration_minutes)
                                    {{ $trade->duration_minutes >= 60 ? floor($trade->duration_minutes / 60) . 'j ' . ($trade->duration_minutes % 60) . 'm' : $trade->duration_minutes . 'm' }}
                                @else
                                    -
                                @endif
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

        <div class="mt-4 flex justify-center">
            {{ $trades->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>
@endsection
