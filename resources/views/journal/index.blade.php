@extends('layouts.app')
@section('page-title', 'Jurnal Trading')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-book-open mr-2 text-cyan-400"></i>Jurnal Trading
        </h1>
        <p class="text-sm mt-1" style="color: var(--text-secondary);">Catat analisis dan pelajaran dari setiap trade</p>
    </div>
    <button onclick="document.getElementById('addJournalModal').classList.remove('hidden')" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium shrink-0">
        <i class="fas fa-plus"></i>Tambah Jurnal
    </button>
</div>

{{-- Add Journal Modal --}}
<div id="addJournalModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="card p-6 relative z-10 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-plus-circle mr-2 text-cyan-400"></i>Tambah Jurnal
        </h3>
        <form action="{{ route('journal.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Currency Pair *</label>
                        <input type="text" name="currency_pair" required placeholder="EURUSD" class="dark-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Tipe *</label>
                        <select name="trade_type" required class="dark-input w-full">
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">P&L *</label>
                        <input type="number" step="any" name="profit_loss" required placeholder="0.00" class="dark-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Hasil *</label>
                        <select name="result" required class="dark-input w-full">
                            <option value="win">Win</option>
                            <option value="loss">Loss</option>
                            <option value="break_even">Break Even</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Strategi</label>
                    <input type="text" name="strategy_used" placeholder="Contoh: Breakout + RSI" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kondisi Market</label>
                    <select name="market_condition" class="dark-input w-full">
                        <option value="">-- Pilih --</option>
                        <option value="trending">Trending</option>
                        <option value="ranging">Ranging</option>
                        <option value="volatile">Volatile</option>
                        <option value="calm">Calm</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Emotion Score (1-5)</label>
                    <input type="number" name="emotion_score" min="1" max="5" placeholder="1-5" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Analisis</label>
                    <textarea name="analysis" rows="3" placeholder="Deskripsikan analisis trade Anda..." class="dark-input w-full resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Pelajaran</label>
                    <textarea name="lesson_learned" rows="3" placeholder="Apa yang bisa dipelajari?" class="dark-input w-full resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('addJournalModal').classList.add('hidden')" class="btn-secondary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">Batal</button>
                <button type="submit" class="btn-primary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Journal Entries --}}
@if($entries->isEmpty())
    <div class="card p-12 text-center">
        <div class="w-20 h-20 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background-color: var(--bg-secondary);">
            <i class="fas fa-book-open text-3xl" style="color: var(--text-secondary);"></i>
        </div>
        <h3 class="font-bold text-lg text-white mb-2">Belum Ada Jurnal</h3>
        <p class="text-sm mb-6 max-w-xs mx-auto" style="color: var(--text-secondary);">
            Mulai catat analisis dari setiap trade untuk meningkatkan performa.
        </p>
        <button onclick="document.getElementById('addJournalModal').classList.remove('hidden')" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium">
            <i class="fas fa-plus"></i>Tambah Jurnal Pertama
        </button>
    </div>
@else
    <div class="space-y-3">
        @foreach($entries as $entry)
        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-white text-sm">{{ $entry->currency_pair }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $entry->trade_type === 'buy' ? 'bg-emerald-900/30 text-emerald-400' : 'bg-red-900/30 text-red-400' }}">
                            {{ strtoupper($entry->trade_type) }}
                        </span>
                        @if($entry->result)
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $entry->result === 'win' ? 'bg-emerald-900/30 text-emerald-400' : ($entry->result === 'loss' ? 'bg-red-900/30 text-red-400' : 'bg-yellow-900/30 text-yellow-400') }}">
                                {{ strtoupper($entry->result) }}
                            </span>
                        @endif
                        @if($entry->auto_imported)
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-cyan-900/30 text-cyan-400">
                                <i class="fas fa-robot mr-0.5"></i>Auto
                            </span>
                        @endif
                    </div>

                    <div class="font-bold mt-1 {{ $entry->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ format_signed_currency($entry->profit_loss, $entry->tradeHistory?->tradingAccount?->currency) }}
                    </div>

                    @if($entry->strategy_used)
                        <p class="text-xs mt-1" style="color: var(--text-secondary);">
                            <i class="fas fa-chess mr-1"></i>{{ $entry->strategy_used }}
                        </p>
                    @endif

                    @if($entry->analysis)
                        <p class="text-xs mt-2 text-gray-300">{{ Str::limit($entry->analysis, 150) }}</p>
                    @endif

                    @if($entry->emotion_score)
                        <div class="flex items-center gap-1 mt-2">
                            <span class="text-[10px]" style="color: var(--text-secondary);">Mood:</span>
                            @for($i = 1; $i <= 5; $i++)
                                <span class="text-xs {{ $i <= $entry->emotion_score ? 'text-yellow-400' : 'text-gray-700' }}">★</span>
                            @endfor
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('journal.edit', $entry->id) }}" class="text-gray-500 hover:text-cyan-400 transition-colors p-2" title="Edit">
                        <i class="fas fa-edit text-xs"></i>
                    </a>
                    <form action="{{ route('journal.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Hapus jurnal ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors p-2" title="Hapus">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 flex justify-center">
        {{ $entries->links('vendor.pagination.tailwind') }}
    </div>
@endif
@endsection
