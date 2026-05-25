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
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Pair *</label>
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

            {{-- Tags --}}
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                    <i class="fas fa-tags mr-1"></i>Tags <span class="text-[10px] opacity-60">(pisah koma)</span>
                </label>
                <input type="text" name="tags" value="{{ $entry->tags ?? '' }}" placeholder="breakout, scalping, news" class="dark-input w-full">
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

            {{-- Emotion picker --}}
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Emotion Score (1-5)</label>
                <div class="flex gap-2">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" onclick="setEmotion({{ $i }})" class="emotion-btn w-9 h-9 rounded-lg border flex items-center justify-center text-sm font-bold transition-colors" style="border-color: var(--border-color); color: var(--text-secondary);" data-val="{{ $i }}">{{ $i }}</button>
                    @endfor
                </div>
                <input type="hidden" name="emotion_score" id="edit_emotion" value="{{ $entry->emotion_score ?? '' }}">
            </div>

            {{-- Quill Analysis --}}
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Analisis</label>
                <div id="edit-quill-editor" class="rounded-lg overflow-hidden" style="border: 1px solid var(--border-color);">
                    <div id="edit-quill-toolbar" class="flex flex-wrap gap-1 px-2 py-1.5" style="background-color: var(--bg-secondary);">
                        <button type="button" class="ql-bold px-2 py-1 rounded text-xs" style="color: var(--text-secondary);">B</button>
                        <button type="button" class="ql-italic px-2 py-1 rounded text-xs" style="color: var(--text-secondary);"><em>I</em></button>
                        <button type="button" class="ql-underline px-2 py-1 rounded text-xs" style="color: var(--text-secondary);"><u>U</u></button>
                        <span class="mx-1" style="color: var(--border-color);">|</span>
                        <button type="button" class="ql-list px-2 py-1 rounded text-xs" value="bullet" style="color: var(--text-secondary);"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="ql-list px-2 py-1 rounded text-xs" value="ordered" style="color: var(--text-secondary);"><i class="fas fa-list-ol"></i></button>
                    </div>
                    <div id="edit-quill-content" class="p-3 text-sm min-h-[100px]" style="background-color: var(--bg-primary); color: var(--text-primary);"></div>
                </div>
                <textarea name="analysis" id="edit_analysis_hidden" class="hidden">{{ $entry->analysis ?? '' }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Pelajaran</label>
                <textarea name="lesson_learned" rows="3" class="dark-input w-full resize-none">{{ $entry->lesson_learned ?? '' }}</textarea>
            </div>

            {{-- Screenshot preview --}}
            @if($entry->screenshot_path)
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Screenshot</label>
                <div class="relative inline-block">
                    <img src="{{ Storage::disk('public')->url($entry->screenshot_path) }}" alt="Screenshot" class="rounded-lg max-h-40 border" style="border-color: var(--border-color);">
                    <form action="{{ route('journal.delete-screenshot', $entry->id) }}" method="POST" class="absolute -top-2 -right-2">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-6 h-6 bg-red-600 rounded-full flex items-center justify-center hover:bg-red-500">
                            <i class="fas fa-times text-white text-[10px]"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('journal.index') }}" class="btn-secondary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-center">Batal</a>
            <button type="submit" class="btn-primary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium" onclick="syncEditQuill()">
                <i class="fas fa-save mr-1"></i>Simpan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<style>
    .ql-toolbar.ql-snow { border: none !important; padding: 4px 8px !important; }
    .ql-container.ql-snow { border: none !important; font-family: inherit !important; font-size: inherit !important; }
    .ql-editor { min-height: 100px; padding: 12px !important; color: inherit; }
    .ql-editor.ql-blank::before { color: rgba(156, 163, 175, 0.5); font-style: italic; }
    .ql-snow .ql-stroke { stroke: #9ca3af; }
    .ql-snow .ql-fill { fill: #9ca3af; }
    .ql-snow .ql-picker-label { color: #9ca3af; }
    .ql-snow .ql-picker-options { background: #1f2937; border-color: #374151; }
    .ql-snow .ql-picker-item { color: #9ca3af; }
    .ql-snow .ql-picker-item:hover { color: #fff; }
    .ql-snow button:hover .ql-stroke { stroke: #fff; }
    .ql-snow button:hover .ql-fill { fill: #fff; }
</style>
<script>
var editQuill = null;
var initialEmotion = {{ $entry->emotion_score ?? 'null' }};

document.addEventListener('DOMContentLoaded', function() {
    editQuill = new Quill('#edit-quill-content', {
        theme: 'snow',
        placeholder: 'Tulis analisis...',
        modules: { toolbar: '#edit-quill-toolbar' }
    });

    // Load existing analysis HTML
    var existingAnalysis = document.getElementById('edit_analysis_hidden').value.trim();
    if (existingAnalysis) {
        editQuill.root.innerHTML = existingAnalysis;
    }

    // Set initial emotion
    if (initialEmotion) setEmotion(initialEmotion);
});

function syncEditQuill() {
    document.getElementById('edit_analysis_hidden').value = editQuill ? editQuill.root.innerHTML : '';
}

function setEmotion(val) {
    document.getElementById('edit_emotion').value = val;
    document.querySelectorAll('.emotion-btn').forEach(function(btn) {
        var v = parseInt(btn.getAttribute('data-val'));
        if (v <= val) {
            btn.style.background = 'rgba(250, 204, 21, 0.2)';
            btn.style.borderColor = '#facc15';
            btn.style.color = '#facc15';
        } else {
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
        }
    });
}
</script>
@endpush
@endsection
