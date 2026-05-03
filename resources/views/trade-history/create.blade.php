@extends('layouts.app')
@section('page-title', 'Tambah Trade')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('trade-history.index') }}" class="text-gray-400 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-plus-circle text-cyan-400 mr-2"></i>Tambah Trade
        </h1>
    </div>
    <p class="text-gray-400 text-sm">Catat trade manual — bisa dari HP maupun desktop.</p>
</div>

<form method="POST" action="{{ route('trade-history.store') }}" id="tradeForm">
    @csrf

    {{-- Akun Trading --}}
    <div class="card p-4 sm:p-5 mb-4">
        <label class="block text-sm font-medium text-gray-300 mb-2">
            <i class="fas fa-wallet mr-1 text-cyan-400"></i> Akun Trading *
        </label>
        <select name="trading_account_id" required class="dark-input w-full">
            <option value="">Pilih akun...</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" {{ old('trading_account_id') == $account->id ? 'selected' : '' }}>
                    {{ $account->account_name ?? $account->account_number }} — {{ $account->broker }} ({{ $account->currency_symbol() }}) [{{ $account->platform }}]
                </option>
            @endforeach
        </select>
    </div>

    {{-- Pair & Type --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="card p-4 sm:p-5">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-exchange-alt mr-1 text-cyan-400"></i> Pair *
            </label>
            <input type="text" name="currency_pair" required
                value="{{ old('currency_pair') }}"
                placeholder="EURUSD"
                class="dark-input w-full uppercase"
                list="pairs-list">
            <datalist id="pairs-list">
                <option value="EURUSD">
                <option value="GBPUSD">
                <option value="USDJPY">
                <option value="AUDUSD">
                <option value="USDCAD">
                <option value="NZDUSD">
                <option value="USDCHF">
                <option value="XAUUSD">
                <option value="GBPJPY">
                <option value="EURJPY">
                <option value="EURGBP">
                <option value="GBPJPY">
                <option value="AUDJPY">
                <option value="NZDJPY">
                <option value="CADJPY">
                <option value="CHFJPY">
            </datalist>
        </div>

        <div class="card p-4 sm:p-5">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-arrows-alt-v mr-1 text-cyan-400"></i> Tipe *
            </label>
            <select name="trade_type" required class="dark-input w-full">
                <option value="">Pilih...</option>
                <option value="buy" {{ old('trade_type') === 'buy' ? 'selected' : '' }}>Buy</option>
                <option value="sell" {{ old('trade_type') === 'sell' ? 'selected' : '' }}>Sell</option>
                <option value="buy_limit" {{ old('trade_type') === 'buy_limit' ? 'selected' : '' }}>Buy Limit</option>
                <option value="sell_limit" {{ old('trade_type') === 'sell_limit' ? 'selected' : '' }}>Sell Limit</option>
                <option value="buy_stop" {{ old('trade_type') === 'buy_stop' ? 'selected' : '' }}>Buy Stop</option>
                <option value="sell_stop" {{ old('trade_type') === 'sell_stop' ? 'selected' : '' }}>Sell Stop</option>
            </select>
        </div>
    </div>

    {{-- Lot & P&L --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="card p-4 sm:p-5">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-layer-group mr-1 text-cyan-400"></i> Lot Size *
            </label>
            <input type="number" name="lot_size" required step="0.01" min="0.01"
                value="{{ old('lot_size') }}"
                placeholder="0.10"
                class="dark-input w-full">
        </div>

        <div class="card p-4 sm:p-5">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                <i class="fas fa-dollar-sign mr-1 text-cyan-400"></i> Profit / Loss *
            </label>
            <input type="number" name="profit_loss" required step="0.01"
                value="{{ old('profit_loss') }}"
                placeholder="0.00"
                class="dark-input w-full"
                id="profit_loss">
        </div>
    </div>

    {{-- Prices --}}
    <div class="card p-4 sm:p-5 mb-4">
        <button type="button" onclick="toggleSection('prices-section')"
            class="flex items-center justify-between w-full text-sm font-medium text-gray-300">
            <span><i class="fas fa-chart-bar mr-1 text-cyan-400"></i> Harga (opsional)</span>
            <i id="prices-section-icon" class="fas fa-chevron-down text-gray-500 transition-transform"></i>
        </button>
        <div id="prices-section" class="hidden mt-3 grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Open Price</label>
                <input type="number" name="open_price" step="0.00001"
                    value="{{ old('open_price') }}"
                    placeholder="1.08500"
                    class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Close Price</label>
                <input type="number" name="close_price" step="0.00001"
                    value="{{ old('close_price') }}"
                    placeholder="1.08750"
                    class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Stop Loss</label>
                <input type="number" name="stop_loss" step="0.00001"
                    value="{{ old('stop_loss') }}"
                    placeholder="1.08300"
                    class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Take Profit</label>
                <input type="number" name="take_profit" step="0.00001"
                    value="{{ old('take_profit') }}"
                    placeholder="1.09000"
                    class="dark-input w-full">
            </div>
        </div>
    </div>

    {{-- Swap & Commission --}}
    <div class="card p-4 sm:p-5 mb-4">
        <button type="button" onclick="toggleSection('fees-section')"
            class="flex items-center justify-between w-full text-sm font-medium text-gray-300">
            <span><i class="fas fa-coins mr-1 text-cyan-400"></i> Swap & Komisi (opsional)</span>
            <i id="fees-section-icon" class="fas fa-chevron-down text-gray-500 transition-transform"></i>
        </button>
        <div id="fees-section" class="hidden mt-3 grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Swap</label>
                <input type="number" name="swap" step="0.01"
                    value="{{ old('swap') }}"
                    placeholder="0.00"
                    class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Komisi</label>
                <input type="number" name="commission" step="0.01"
                    value="{{ old('commission') }}"
                    placeholder="0.00"
                    class="dark-input w-full">
            </div>
        </div>
    </div>

    {{-- Dates --}}
    <div class="card p-4 sm:p-5 mb-4">
        <button type="button" onclick="toggleSection('dates-section')"
            class="flex items-center justify-between w-full text-sm font-medium text-gray-300">
            <span><i class="fas fa-calendar-alt mr-1 text-cyan-400"></i> Tanggal (opsional)</span>
            <i id="dates-section-icon" class="fas fa-chevron-down text-gray-500 transition-transform"></i>
        </button>
        <div id="dates-section" class="hidden mt-3 grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Open Date</label>
                <input type="date" name="open_date"
                    value="{{ old('open_date', now()->format('Y-m-d')) }}"
                    class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Close Date</label>
                <input type="date" name="close_date"
                    value="{{ old('close_date', now()->format('Y-m-d')) }}"
                    class="dark-input w-full">
            </div>
        </div>
    </div>

    {{-- Comment --}}
    <div class="card p-4 sm:p-5 mb-4">
        <button type="button" onclick="toggleSection('comment-section')"
            class="flex items-center justify-between w-full text-sm font-medium text-gray-300">
            <span><i class="fas fa-sticky-note mr-1 text-cyan-400"></i> Catatan (opsional)</span>
            <i id="comment-section-icon" class="fas fa-chevron-down text-gray-500 transition-transform"></i>
        </button>
        <div id="comment-section" class="hidden mt-3">
            <textarea name="comment" rows="3"
                placeholder="Tulis catatan tentang trade ini..."
                class="dark-input w-full resize-none">{{ old('comment') }}</textarea>
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <button type="submit"
            class="btn-primary px-6 py-3 rounded-lg font-semibold text-center flex-1 sm:flex-none">
            <i class="fas fa-check mr-2"></i>Simpan Trade
        </button>
        <a href="{{ route('trade-history.index') }}"
            class="btn-secondary px-6 py-3 rounded-lg font-semibold text-center">
            Batal
        </a>
    </div>
</form>

<script>
function toggleSection(id) {
    const section = document.getElementById(id);
    const icon = document.getElementById(id + '-icon');
    section.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}
</script>
@endsection