@extends('layouts.app')
@section('page-title', 'Tambah Trade')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('trade-history.index') }}" class="text-cyan-400 hover:text-cyan-300 text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Riwayat Trade
        </a>
    </div>
    <h1 class="text-xl sm:text-2xl font-bold text-white">
        <i class="fas fa-plus-circle mr-2 text-emerald-400"></i>Tambah Trade Manual
    </h1>
    <p class="text-sm mt-1" style="color: var(--text-secondary);">Catat trade yang tidak ter-import otomatis</p>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-emerald-900/30 border border-emerald-700/30 text-emerald-400 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-900/30 border border-red-700/30 text-red-400 text-sm">
        <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
    </div>
@endif

@if($accounts->isEmpty())
    <div class="card p-6 text-center">
        <i class="fas fa-exclamation-triangle text-3xl text-yellow-400 mb-3"></i>
        <p class="text-white font-medium mb-2">Belum ada akun trading</p>
        <p class="text-sm mb-4" style="color: var(--text-secondary);">Tambahkan akun trading terlebih dahulu.</p>
        <a href="{{ route('trading-accounts.index') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-plus"></i>Tambah Akun
        </a>
    </div>
@else
    <form method="POST" action="{{ route('trade-history.store') }}" class="space-y-4">
        @csrf

        {{-- Akun Trading --}}
        <div class="card p-5 sm:p-6">
            <h3 class="font-semibold text-white mb-3">
                <i class="fas fa-wallet mr-2 text-cyan-400"></i>Akun Trading
            </h3>
            <select name="trading_account_id" class="dark-input w-full text-sm" required>
                <option value="">Pilih akun...</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->broker }} — {{ $acc->account_number }}</option>
                @endforeach
            </select>
        </div>

        {{-- Info Trade --}}
        <div class="card p-5 sm:p-6">
            <h3 class="font-semibold text-white mb-3">
                <i class="fas fa-info-circle mr-2 text-cyan-400"></i>Info Trade
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Currency Pair *</label>
                    <input type="text" name="currency_pair" class="dark-input w-full text-sm" placeholder="EURUSD" required maxlength="20">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Tipe *</label>
                    <select name="trade_type" class="dark-input w-full text-sm" required>
                        <option value="buy">Buy</option>
                        <option value="sell">Sell</option>
                        <option value="buy_limit">Buy Limit</option>
                        <option value="sell_limit">Sell Limit</option>
                        <option value="buy_stop">Buy Stop</option>
                        <option value="sell_stop">Sell Stop</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Lot Size *</label>
                    <input type="number" name="lot_size" class="dark-input w-full text-sm" step="0.01" min="0.01" placeholder="0.10" required>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Profit / Loss *</label>
                    <input type="number" name="profit_loss" class="dark-input w-full text-sm" step="0.01" placeholder="25.00" required>
                </div>
            </div>
        </div>

        {{-- Harga --}}
        <div class="card p-5 sm:p-6">
            <h3 class="font-semibold text-white mb-3">
                <i class="fas fa-dollar-sign mr-2 text-cyan-400"></i>Harga
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Open Price</label>
                    <input type="number" name="open_price" class="dark-input w-full text-sm" step="any" placeholder="1.08500">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Close Price</label>
                    <input type="number" name="close_price" class="dark-input w-full text-sm" step="any" placeholder="1.08750">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Stop Loss</label>
                    <input type="number" name="stop_loss" class="dark-input w-full text-sm" step="any" placeholder="1.08300">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Take Profit</label>
                    <input type="number" name="take_profit" class="dark-input w-full text-sm" step="any" placeholder="1.09000">
                </div>
            </div>
        </div>

        {{-- Tanggal & Biaya --}}
        <div class="card p-5 sm:p-6">
            <h3 class="font-semibold text-white mb-3">
                <i class="fas fa-calendar-alt mr-2 text-cyan-400"></i>Tanggal & Biaya
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Open Date</label>
                    <input type="datetime-local" name="open_date" class="dark-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Close Date</label>
                    <input type="datetime-local" name="close_date" class="dark-input w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Swap</label>
                    <input type="number" name="swap" class="dark-input w-full text-sm" step="0.01" placeholder="0.00" value="0">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Commission</label>
                    <input type="number" name="commission" class="dark-input w-full text-sm" step="0.01" placeholder="0.00" value="0">
                </div>
            </div>
        </div>

        {{-- Catatan --}}
        <div class="card p-5 sm:p-6">
            <h3 class="font-semibold text-white mb-3">
                <i class="fas fa-sticky-note mr-2 text-cyan-400"></i>Catatan
            </h3>
            <textarea name="comment" class="dark-input w-full text-sm" rows="3" placeholder="Catatan trade (opsional)..." maxlength="500"></textarea>
        </div>

        {{-- Submit --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" class="btn-primary flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium">
                <i class="fas fa-save"></i>Simpan Trade
            </button>
            <a href="{{ route('trade-history.index') }}" class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium bg-gray-700 hover:bg-gray-600 text-gray-300 transition">
                Batal
            </a>
        </div>
    </form>
@endif
@endsection
