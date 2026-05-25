@extends('layouts.app')
@section('page-title', 'Edit Trade')

@section('content')
<div class="mb-6">
    <a href="{{ route('trade-history.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition-colors">
        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Riwayat Trade
    </a>
    <h1 class="text-xl sm:text-2xl font-bold text-white mt-2">
        <i class="fas fa-edit mr-2 text-cyan-400"></i>Edit Trade #{{ $trade->ticket }}
    </h1>
</div>

<div class="max-w-2xl">
    <div class="card p-5 sm:p-6">
        <form method="POST" action="{{ route('trade-history.update', $trade->id) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Currency Pair *</label>
                    <input type="text" name="currency_pair" value="{{ old('currency_pair', $trade->currency_pair) }}" required
                           class="dark-input w-full" placeholder="EURUSD">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Tipe *</label>
                    <select name="trade_type" required class="dark-input w-full">
                        @foreach(['buy' => 'Buy', 'sell' => 'Sell', 'buy_limit' => 'Buy Limit', 'sell_limit' => 'Sell Limit', 'buy_stop' => 'Buy Stop', 'sell_stop' => 'Sell Stop'] as $val => $label)
                            <option value="{{ $val }}" {{ old('trade_type', $trade->trade_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Lot Size *</label>
                    <input type="number" step="0.01" min="0.01" name="lot_size" value="{{ old('lot_size', $trade->lot_size) }}" required
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Profit/Loss *</label>
                    <input type="number" step="any" name="profit_loss" value="{{ old('profit_loss', $trade->profit_loss) }}" required
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Harga Buka</label>
                    <input type="number" step="any" name="open_price" value="{{ old('open_price', $trade->open_price) }}"
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Harga Tutup</label>
                    <input type="number" step="any" name="close_price" value="{{ old('close_price', $trade->close_price) }}"
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Stop Loss</label>
                    <input type="number" step="any" name="stop_loss" value="{{ old('stop_loss', $trade->stop_loss) }}"
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Take Profit</label>
                    <input type="number" step="any" name="take_profit" value="{{ old('take_profit', $trade->take_profit) }}"
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Swap</label>
                    <input type="number" step="any" name="swap" value="{{ old('swap', $trade->swap) }}"
                           class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Commission</label>
                    <input type="number" step="any" name="commission" value="{{ old('commission', $trade->commission) }}"
                           class="dark-input w-full">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Comment</label>
                <textarea name="comment" rows="2" class="dark-input w-full resize-none">{{ old('comment', $trade->comment) }}</textarea>
            </div>
            <div class="flex items-center gap-3 mt-6">
                <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
                <a href="{{ route('trade-history.index') }}" class="btn-secondary px-5 py-2.5 rounded-lg text-sm font-medium">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
