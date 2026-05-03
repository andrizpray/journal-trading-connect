@extends('layouts.app')
@section('page-title', 'Jurnal Trading')

@section('content')
{{-- Header --}}
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-book-open mr-2 text-cyan-400"></i>Jurnal Trading
        </h1>
        <p class="text-sm mt-1" style="color: var(--text-secondary);">
            Catat analisis dan pelajaran dari setiap trade
            <span class="mx-1">·</span>
            <a href="{{ route('journal.review') }}" class="text-cyan-400 hover:text-cyan-300">Review {{ now()->format('M') }} →</a>
        </p>
    </div>
    <div class="flex gap-2 shrink-0">
        <a href="{{ route('journal.review') }}" class="btn-secondary flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium">
            <i class="fas fa-calendar-alt"></i><span class="hidden sm:inline">Review</span>
        </a>
        <button onclick="openJournalModal('post_trade')" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium">
            <i class="fas fa-plus"></i>Tambah Jurnal
        </button>
    </div>
</div>

{{-- Search & Filter --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('journal.index') }}" class="flex flex-wrap gap-3 items-end">
        {{-- 3.6 Search --}}
        <div class="flex-1 min-w-[180px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Analisis, pair, strategi..." class="dark-input w-full text-sm py-2">
        </div>
        {{-- Filter result --}}
        <div class="flex-1 min-w-[110px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Hasil</label>
            <select name="result" class="dark-input w-full text-sm py-2">
                <option value="">Semua</option>
                <option value="win" {{ request('result') === 'win' ? 'selected' : '' }}>Win</option>
                <option value="loss" {{ request('result') === 'loss' ? 'selected' : '' }}>Loss</option>
                <option value="break_even" {{ request('result') === 'break_even' ? 'selected' : '' }}>Break Even</option>
            </select>
        </div>
        {{-- 3.3 Filter by tag --}}
        <div class="flex-1 min-w-[120px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Tag</label>
            <select name="tag" class="dark-input w-full text-sm py-2">
                <option value="">Semua Tag</option>
                @foreach($allTags as $tag)
                    <option value="{{ $tag }}" {{ request('tag') === $tag ? 'selected' : '' }}>{{ $tag }}</option>
                @endforeach
            </select>
        </div>
        {{-- Filter template --}}
        <div class="flex-1 min-w-[120px]">
            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Tipe</label>
            <select name="template" class="dark-input w-full text-sm py-2">
                <option value="">Semua</option>
                <option value="post_trade" {{ request('template') === 'post_trade' ? 'selected' : '' }}>Post-Trade</option>
                <option value="pre_trade" {{ request('template') === 'pre_trade' ? 'selected' : '' }}>Pre-Trade Plan</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary px-3 py-2 rounded-lg text-sm"><i class="fas fa-filter"></i></button>
            <a href="{{ route('journal.index') }}" class="btn-secondary px-3 py-2 rounded-lg text-sm"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

{{-- Add Journal Modal --}}
<div id="addJournalModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60" onclick="closeJournalModal()"></div>
    <div class="card p-6 relative z-10 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-white mb-4" id="journalModalTitle">
            <i class="fas fa-plus-circle mr-2 text-cyan-400"></i>Tambah Jurnal
        </h3>
        <form action="{{ route('journal.store') }}" method="POST">
            @csrf
            <input type="hidden" name="template_type" id="modal_template_type" value="post_trade">

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Pair *</label>
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
                        <input type="number" step="any" name="profit_loss" required placeholder="0.00" class="dark-input w-full" id="modal_pnl_field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Hasil *</label>
                        <select name="result" required class="dark-input w-full" id="modal_result_field">
                            <option value="win">Win</option>
                            <option value="loss">Loss</option>
                            <option value="break_even">Break Even</option>
                        </select>
                    </div>
                </div>

                {{-- 3.3 Tags --}}
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">
                        <i class="fas fa-tags mr-1"></i>Tags <span class="text-[10px] opacity-60">(pisah dengan koma)</span>
                    </label>
                    <input type="text" name="tags" placeholder="breakout, scalping, news" class="dark-input w-full" id="modal_tags_field">
                    <div class="flex flex-wrap gap-1 mt-1.5" id="tagSuggestions">
                        @foreach(['breakout','scalping','swing','news','range','trend','reversal','support','resistance','scam'] as $sug)
                            <button type="button" onclick="addTag('{{ $sug }}')" class="text-[10px] px-2 py-0.5 rounded-full border cursor-pointer hover:bg-gray-700 transition-colors" style="border-color: var(--border-color); color: var(--text-secondary);">{{ $sug }}</button>
                        @endforeach
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
                    <div class="flex gap-2" id="emotionPicker">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" onclick="setEmotion({{ $i }})" class="emotion-btn w-9 h-9 rounded-lg border flex items-center justify-center text-sm font-bold transition-colors" style="border-color: var(--border-color); color: var(--text-secondary);" data-val="{{ $i }}">{{ $i }}</button>
                        @endfor
                    </div>
                    <input type="hidden" name="emotion_score" id="modal_emotion" value="">
                    <div class="flex justify-between text-[10px] mt-1" style="color: var(--text-secondary);">
                        <span>😰 Stres</span><span>😌 Tenang</span>
                    </div>
                </div>

                {{-- 3.1 — Rich text Analysis (Quill) --}}
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Analisis</label>
                    <div id="quill-editor" class="rounded-lg overflow-hidden" style="border: 1px solid var(--border-color);">
                        <div id="quill-toolbar" class="flex flex-wrap gap-1 px-2 py-1.5" style="background-color: var(--bg-secondary);">
                            <button type="button" class="ql-bold px-2 py-1 rounded text-xs" style="color: var(--text-secondary);">B</button>
                            <button type="button" class="ql-italic px-2 py-1 rounded text-xs" style="color: var(--text-secondary);"><em>I</em></button>
                            <button type="button" class="ql-underline px-2 py-1 rounded text-xs" style="color: var(--text-secondary);"><u>U</u></button>
                            <span class="mx-1" style="color: var(--border-color);">|</span>
                            <button type="button" class="ql-list px-2 py-1 rounded text-xs" value="bullet" style="color: var(--text-secondary);"><i class="fas fa-list-ul"></i></button>
                            <button type="button" class="ql-list px-2 py-1 rounded text-xs" value="ordered" style="color: var(--text-secondary);"><i class="fas fa-list-ol"></i></button>
                        </div>
                        <div id="quill-content" class="quill-body p-3 text-sm min-h-[100px]" style="background-color: var(--bg-primary); color: var(--text-primary);"></div>
                    </div>
                    <textarea name="analysis" id="analysis_hidden" class="hidden"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Pelajaran</label>
                    <textarea name="lesson_learned" rows="3" placeholder="Apa yang bisa dipelajari?" class="dark-input w-full resize-none"></textarea>
                </div>

                {{-- 3.4 Pre-Trade Plan (hidden by default, shown when template = pre_trade) --}}
                <div id="planFields" class="hidden border-t pt-4 space-y-3" style="border-color: var(--border-color);">
                    <p class="text-xs font-semibold text-cyan-400"><i class="fas fa-chess mr-1"></i>Trading Plan</p>
                    <div>
                        <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Setup</label>
                        <input type="text" name="plan_setup" placeholder="Contoh: Double top di H4" class="dark-input w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Alasan Entry</label>
                        <input type="text" name="plan_entry" placeholder="Contoh: Break neckline + RSI oversold" class="dark-input w-full text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Stop Loss</label>
                            <input type="number" step="any" name="plan_sl" placeholder="0.00000" class="dark-input w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Take Profit</label>
                            <input type="number" step="any" name="plan_tp" placeholder="0.00000" class="dark-input w-full text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium mb-1" style="color: var(--text-secondary);">Reasoning</label>
                        <textarea name="plan_reasoning" rows="2" placeholder="Kenapa setup ini bagus?" class="dark-input w-full resize-none text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeJournalModal()" class="btn-secondary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">Batal</button>
                <button type="submit" class="btn-primary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium" onclick="syncQuill()">
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
        <button onclick="openJournalModal('post_trade')" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium">
            <i class="fas fa-plus"></i>Tambah Jurnal Pertama
        </button>
    </div>
@else
    <div class="space-y-3">
        @foreach($entries as $entry)
        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    {{-- Header: pair, type, result, template badge --}}
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
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-cyan-900/30 text-cyan-400"><i class="fas fa-robot mr-0.5"></i>Auto</span>
                        @endif
                        @if($entry->template_type === 'pre_trade')
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-purple-900/30 text-purple-400"><i class="fas fa-chess mr-0.5"></i>Plan</span>
                        @endif
                    </div>

                    {{-- P&L --}}
                    <div class="font-bold mt-1 {{ $entry->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ format_signed_currency($entry->profit_loss, $entry->tradeHistory?->tradingAccount?->currency) }}
                    </div>

                    {{-- 3.3 Tags --}}
                    @if($entry->tag_list)
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($entry->tag_list as $tag)
                                <a href="{{ route('journal.index', ['tag' => $tag]) }}" class="text-[10px] px-2 py-0.5 rounded-full border hover:bg-gray-700 transition-colors" style="border-color: var(--border-color); color: var(--text-secondary);">
                                    <i class="fas fa-tag mr-0.5"></i>{{ $tag }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Strategy --}}
                    @if($entry->strategy_used)
                        <p class="text-xs mt-1" style="color: var(--text-secondary);">
                            <i class="fas fa-chess mr-1"></i>{{ $entry->strategy_used }}
                        </p>
                    @endif

                    {{-- 3.4 Plan info --}}
                    @if($entry->template_type === 'pre_trade' && ($entry->plan_setup || $entry->plan_entry))
                        <div class="mt-2 p-2 rounded-lg text-xs" style="background-color: var(--bg-secondary);">
                            @if($entry->plan_setup)
                                <div><span class="font-medium text-white">Setup:</span> <span style="color: var(--text-secondary);">{{ $entry->plan_setup }}</span></div>
                            @endif
                            @if($entry->plan_entry)
                                <div><span class="font-medium text-white">Entry:</span> <span style="color: var(--text-secondary);">{{ $entry->plan_entry }}</span></div>
                            @endif
                            @if($entry->plan_sl)
                                <div><span class="font-medium text-white">SL:</span> <span class="text-red-400">{{ $entry->plan_sl }}</span></div>
                            @endif
                            @if($entry->plan_tp)
                                <div><span class="font-medium text-white">TP:</span> <span class="text-emerald-400">{{ $entry->plan_tp }}</span></div>
                            @endif
                        </div>
                    @endif

                    {{-- 3.1 Analysis (HTML from Quill) --}}
                    @if($entry->analysis)
                        <div class="text-xs mt-2 text-gray-300 journal-content">{{ $entry->analysis }}</div>
                    @endif

                    {{-- Lesson --}}
                    @if($entry->lesson_learned)
                        <p class="text-xs mt-1.5 italic" style="color: var(--text-secondary);">
                            💡 {{ Str::limit($entry->lesson_learned, 200) }}
                        </p>
                    @endif

                    {{-- Emotion + screenshot --}}
                    <div class="flex items-center gap-3 mt-2">
                        @if($entry->emotion_score)
                            <div class="flex items-center gap-1">
                                <span class="text-[10px]" style="color: var(--text-secondary);">Mood:</span>
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-xs {{ $i <= $entry->emotion_score ? 'text-yellow-400' : 'text-gray-700' }}">★</span>
                                @endfor
                            </div>
                        @endif
                        @if($entry->screenshot_path)
                            <a href="{{ Storage::disk('public')->url($entry->screenshot_path) }}" target="_blank" class="text-[10px] text-cyan-400 hover:text-cyan-300">
                                <i class="fas fa-image mr-0.5"></i>Screenshot
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('journal.edit', $entry->id) }}" class="text-gray-500 hover:text-cyan-400 transition-colors p-2" title="Edit">
                        <i class="fas fa-edit text-xs"></i>
                    </a>
                    {{-- 3.2 Screenshot upload --}}
                    <label class="text-gray-500 hover:text-purple-400 transition-colors p-2 cursor-pointer" title="Upload Screenshot">
                        <i class="fas fa-camera text-xs"></i>
                        <input type="file" accept="image/*" class="hidden" onchange="uploadScreenshot({{ $entry->id }}, this)">
                    </label>
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

@push('scripts')
{{-- 3.1 Quill Editor --}}
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
    .journal-content p { margin-bottom: 4px; }
    .journal-content ul, .journal-content ol { padding-left: 16px; margin-bottom: 4px; }
</style>
<script>
var quill = null;

document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#quill-content', {
        theme: 'snow',
        placeholder: 'Tulis analisis trade Anda...',
        modules: {
            toolbar: '#quill-toolbar'
        }
    });
});

function syncQuill() {
    document.getElementById('analysis_hidden').value = quill ? quill.root.innerHTML : '';
}

function openJournalModal(type) {
    document.getElementById('addJournalModal').classList.remove('hidden');
    document.getElementById('modal_template_type').value = type || 'post_trade';
    document.getElementById('planFields').classList.toggle('hidden', type !== 'pre_trade');

    var title = document.getElementById('journalModalTitle');
    if (type === 'pre_trade') {
        title.innerHTML = '<i class="fas fa-chess mr-2 text-purple-400"></i>Pre-Trade Plan';
        document.getElementById('modal_pnl_field').removeAttribute('required');
        document.getElementById('modal_pnl_field').value = '';
        document.getElementById('modal_result_field').removeAttribute('required');
    } else {
        title.innerHTML = '<i class="fas fa-plus-circle mr-2 text-cyan-400"></i>Tambah Jurnal';
        document.getElementById('modal_pnl_field').setAttribute('required', 'required');
        document.getElementById('modal_result_field').setAttribute('required', 'required');
    }
}

function closeJournalModal() {
    document.getElementById('addJournalModal').classList.add('hidden');
    if (quill) quill.setText('');
}

// 3.3 — Tag click to add
function addTag(tag) {
    var input = document.getElementById('modal_tags_field');
    var existing = input.value.split(',').map(function(t) { return t.trim(); }).filter(Boolean);
    if (existing.indexOf(tag) === -1) {
        existing.push(tag);
        input.value = existing.join(', ');
    }
}

// Emotion picker
function setEmotion(val) {
    document.getElementById('modal_emotion').value = val;
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

// 3.2 — Screenshot upload via AJAX
function uploadScreenshot(entryId, input) {
    var file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { alert('File terlalu besar (max 5MB)'); return; }

    var formData = new FormData();
    formData.append('screenshot', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/journal/' + entryId + '/screenshot', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (window.showToast) showToast(data.success, 'success');
            else location.reload();
        } else {
            alert(data.error || 'Gagal upload');
        }
    })
    .catch(function() { alert('Error upload'); });
}
</script>
@endpush
@endsection
