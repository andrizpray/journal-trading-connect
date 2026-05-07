# Laravel Journal Enhancement Pattern

Rich journal system with rich text, screenshots, tags, pre-trade plans, reviews, and search. Used in journal-trading-connect (Phase 3).

## Database: Add Enhancement Columns

One migration to add all journal enhancements:

```php
Schema::table('journal_entries', function (Blueprint $table) {
    $table->string('screenshot_path')->nullable();       // 3.2 — Screenshot
    $table->string('tags')->nullable();                   // 3.3 — Comma-separated tags
    $table->string('template_type')->nullable();          // 3.4 — pre_trade, post_trade, review
    $table->string('plan_setup')->nullable();             // 3.4 — Trading plan fields
    $table->string('plan_entry')->nullable();
    $table->decimal('plan_sl', 20, 8)->nullable();
    $table->decimal('plan_tp', 20, 8)->nullable();
    $table->text('plan_reasoning')->nullable();
});
```

## Model: Tag Accessor

Parse comma-separated tags to array:

```php
public function getTagListAttribute(): array
{
    if (empty($this->tags)) return [];
    return array_map('trim', explode(',', $this->tags));
}
```

## Quill Rich Text Editor (CDN-based)

**Why CDN, not npm:** Quill is a single page feature, not app-wide. npm install pulls in 2MB+ of dependencies for a single editor instance. CDN is simpler for Blade apps.

**Setup in Blade (both add and edit forms):**

```blade
{{-- CDN --}}
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

{{-- Dark theme overrides --}}
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

{{-- Minimal toolbar (bold, italic, underline, lists) --}}
<div id="quill-editor" style="border: 1px solid var(--border-color);">
    <div id="quill-toolbar" class="flex flex-wrap gap-1 px-2 py-1.5" style="background-color: var(--bg-secondary);">
        <button type="button" class="ql-bold px-2 py-1 rounded text-xs">B</button>
        <button type="button" class="ql-italic px-2 py-1 rounded text-xs"><em>I</em></button>
        <button type="button" class="ql-underline px-2 py-1 rounded text-xs"><u>U</u></button>
        <button type="button" class="ql-list px-2 py-1 rounded text-xs" value="bullet"><i class="fas fa-list-ul"></i></button>
        <button type="button" class="ql-list px-2 py-1 rounded text-xs" value="ordered"><i class="fas fa-list-ol"></i></button>
    </div>
    <div id="quill-content" class="p-3 text-sm min-h-[100px]" style="background-color: var(--bg-primary); color: var(--text-primary);"></div>
</div>
{{-- Hidden textarea for form submission --}}
<textarea name="analysis" id="analysis_hidden" class="hidden"></textarea>
```

**JS pattern:**

```js
var quill = null;
document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#quill-content', {
        theme: 'snow',
        placeholder: 'Tulis analisis...',
        modules: { toolbar: '#quill-toolbar' }
    });
});

// Call on form submit
function syncQuill() {
    document.getElementById('analysis_hidden').value = quill ? quill.root.innerHTML : '';
}
```

**For edit form** — load existing HTML content after Quill init:

```js
var existingAnalysis = document.getElementById('edit_analysis_hidden').value.trim();
if (existingAnalysis) {
    editQuill.root.innerHTML = existingAnalysis;
}
```

**Pitfall:** Quill content is HTML. When displaying in Blade, use `{!! $entry->analysis !!}` (not `{{ }}`) or wrap in a div with a class and use CSS to style rendered HTML. Add `.journal-content p { margin-bottom: 4px; }` and `.journal-content ul, .journal-content ol { padding-left: 16px; }`.

## Screenshot Upload via AJAX

**Inline upload button in journal list (no page reload):**

```blade
<label class="text-gray-500 hover:text-purple-400 p-2 cursor-pointer">
    <i class="fas fa-camera text-xs"></i>
    <input type="file" accept="image/*" class="hidden" onchange="uploadScreenshot({{ $entry->id }}, this)">
</label>
```

```js
function uploadScreenshot(entryId, input) {
    var file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { alert('Max 5MB'); return; }

    var formData = new FormData();
    formData.append('screenshot', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/journal/' + entryId + '/screenshot', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) showToast(data.success, 'success');
        else alert(data.error || 'Gagal');
    });
}
```

**Controller** — return JSON for AJAX, redirect for regular POST:

```php
public function uploadScreenshot(Request $request, $id)
{
    // ...validate, store, update...
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json(['success' => 'Screenshot uploaded!']);
    }
    return redirect()->back()->with('success', 'Screenshot uploaded!');
}
```

**Prerequisites:** `php artisan storage:link` for public disk access. Image URL: `Storage::disk('public')->url($entry->screenshot_path)`.

## Tag System (Comma-Separated)

**Why comma-separated, not pivot table:** For a simple tagging system without a `tags` table or pivot, store as comma-separated string. Extract unique tags for filter via collection pipeline:

```php
$allTags = JournalEntry::where('user_id', Auth::id())
    ->whereNotNull('tags')
    ->where('tags', '!=', '')
    ->pluck('tags')
    ->flatMap(fn($t) => array_map('trim', explode(',', $t)))
    ->unique()->sort()->values();
```

**Tag suggestion buttons:**

```blade
<div class="flex flex-wrap gap-1 mt-1.5">
    @foreach(['breakout','scalping','swing','news','range','trend'] as $sug)
        <button type="button" onclick="addTag('{{ $sug }}')" class="text-[10px] px-2 py-0.5 rounded-full border">
            {{ $sug }}
        </button>
    @endforeach
</div>
```

```js
function addTag(tag) {
    var input = document.getElementById('modal_tags_field');
    var existing = input.value.split(',').map(t => t.trim()).filter(Boolean);
    if (existing.indexOf(tag) === -1) {
        existing.push(tag);
        input.value = existing.join(', ');
    }
}
```

**Filter by tag in controller:**

```php
if ($request->filled('tag')) {
    $query->where('tags', 'LIKE', '%' . $request->tag . '%');
}
```

**Clickable tags in list view:**

```blade
@foreach($entry->tag_list as $tag)
    <a href="{{ route('journal.index', ['tag' => $tag]) }}" class="text-[10px] px-2 py-0.5 rounded-full">
        <i class="fas fa-tag mr-0.5"></i>{{ $tag }}
    </a>
@endforeach
```

## Pre-Trade Plan Template

Toggle between post-trade journal and pre-trade plan in the same modal. Key differences:

- Post-trade: P&L and Result are **required**
- Pre-trade: P&L and Result are **optional** (not yet known)
- Pre-trade shows additional fields: Setup, Entry reason, SL, TP, Reasoning

```js
function openJournalModal(type) {
    document.getElementById('modal_template_type').value = type || 'post_trade';
    document.getElementById('planFields').classList.toggle('hidden', type !== 'pre_trade');

    if (type === 'pre_trade') {
        document.getElementById('modal_pnl_field').removeAttribute('required');
        document.getElementById('modal_result_field').removeAttribute('required');
    } else {
        document.getElementById('modal_pnl_field').setAttribute('required', 'required');
        document.getElementById('modal_result_field').setAttribute('required', 'required');
    }
}
```

## Weekly/Monthly Review Page

**Controller pattern** — compute stats for current period and compare with previous:

```php
public function review(Request $request)
{
    $period = $request->filled('period') ? $request->period : 'weekly';
    $now = now();

    if ($period === 'monthly') {
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();
        $prevStart = $now->copy()->subMonth()->startOfMonth();
        $prevEnd = $now->copy()->subMonth()->endOfMonth();
    } else {
        $start = $now->copy()->startOfWeek();
        $end = $now->copy()->endOfWeek();
        $prevStart = $now->copy()->subWeek()->startOfWeek();
        $prevEnd = $now->copy()->subWeek()->endOfWeek();
    }

    // Current period stats...
    // Previous period P&L for comparison...
    $pnlChange = $prevPnl != 0
        ? round((($totalPnl - $prevPnl) / abs($prevPnl)) * 100, 1)
        : null;
}
```

**View elements:** Stats cards, top pairs list, emotion score display, recent lessons.

## Full-Text Search Across Multiple Fields

```php
if ($request->filled('search')) {
    $search = $request->search;
    $query->where(function ($q) use ($search) {
        $q->where('analysis', 'LIKE', '%' . $search . '%')
          ->orWhere('lesson_learned', 'LIKE', '%' . $search . '%')
          ->orWhere('currency_pair', 'LIKE', '%' . $search . '%')
          ->orWhere('strategy_used', 'LIKE', '%' . $search . '%')
          ->orWhere('tags', 'LIKE', '%' . $search . '%')
          ->orWhere('plan_reasoning', 'LIKE', '%' . $search . '%');
    });
}
```

**Pitfall:** LIKE search is not full-text. For large datasets, add MySQL `FULLTEXT` index or use `MATCH() AGAINST()`. For most small-medium projects (under 10k rows), LIKE is sufficient.

## Emotion Score Picker (Star Rating)

```blade
<div class="flex gap-2">
    @for($i = 1; $i <= 5; $i++)
        <button type="button" onclick="setEmotion({{ $i }})"
            class="emotion-btn w-9 h-9 rounded-lg border flex items-center justify-center text-sm font-bold"
            data-val="{{ $i }}">{{ $i }}</button>
    @endfor
</div>
<input type="hidden" name="emotion_score" id="modal_emotion" value="">
```

```js
function setEmotion(val) {
    document.getElementById('modal_emotion').value = val;
    document.querySelectorAll('.emotion-btn').forEach(btn => {
        var v = parseInt(btn.dataset.val);
        btn.style.background = v <= val ? 'rgba(250, 204, 21, 0.2)' : '';
        btn.style.borderColor = v <= val ? '#facc15' : '';
        btn.style.color = v <= val ? '#facc15' : '';
    });
}
```
