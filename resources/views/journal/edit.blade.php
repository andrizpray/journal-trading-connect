@extends('layouts.app')
@section('page-title', 'Edit Jurnal')

@section('content')
<div class="mb-6">
    <a href="{{ route('journal.index') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition-colors">
        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Jurnal
    </a>
</div>

<div class="card p-5 sm:p-6 max-w-lg">
    <h3 class="text-lg font-semibold text-white mb-4">
        <i class="fas fa-edit mr-2 text-cyan-400"></i>Edit Jurnal
    </h3>
    <form action="{{ route('journal.update', $entry->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Currency Pair *</label>
                    <input type="text" name="currency_pair" required value="{{ $entry->currency_pair }}" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Tipe *</label>
                    <select name="trade_type" required class="dark-input w-full">
                        <option value="buy" {{ $entry->trade_type === 'buy' ? 'selected' : '' }}>Buy</option>
                        <option value="sell" {{ $entry->trade_type === 'sell' ? 'selected' : '' }}>Sell</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">P&L *</label>
                    <input type="number" step="any" name="profit_loss" required value="{{ $entry->profit_loss }}" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Hasil *</label>
                    <select name="result" required class="dark-input w-full">
                        <option value="win" {{ $entry->result === 'win' ? 'selected' : '' }}>Win</option>
                        <option value="loss" {{ $entry->result === 'loss' ? 'selected' : '' }}>Loss</option>
                        <option value="break_even" {{ $entry->result === 'break_even' ? 'selected' : '' }}>Break Even</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Strategi</label>
                <input type="text" name="strategy_used" value="{{ $entry->strategy_used ?? '' }}" placeholder="Contoh: Breakout + RSI" class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kondisi Market</label>
                <select name="market_condition" class="dark-input w-full">
                    <option value="">-- Pilih --</option>
                    <option value="trending" {{ ($entry->market_condition ?? '') === 'trending' ? 'selected' : '' }}>Trending</option>
                    <option value="ranging" {{ ($entry->market_condition ?? '') === 'ranging' ? 'selected' : '' }}>Ranging</option>
                    <option value="volatile" {{ ($entry->market_condition ?? '') === 'volatile' ? 'selected' : '' }}>Volatile</option>
                    <option value="calm" {{ ($entry->market_condition ?? '') === 'calm' ? 'selected' : '' }}>Calm</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Emotion Score (1-5)</label>
                <input type="number" name="emotion_score" min="1" max="5" value="{{ $entry->emotion_score ?? '' }}" placeholder="1-5" class="dark-input w-full">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Analisis</label>
                <textarea name="analysis" rows="3" class="dark-input w-full resize-none">{{ $entry->analysis ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Pelajaran</label>
                <textarea name="lesson_learned" rows="3" class="dark-input w-full resize-none">{{ $entry->lesson_learned ?? '' }}</textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <a href="{{ route('journal.index') }}" class="btn-secondary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-center">Batal</a>
            <button type="submit" class="btn-primary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">
                <i class="fas fa-save mr-1"></i>Simpan
            </button>
        </div>
    </form>
</div>
@endsection
