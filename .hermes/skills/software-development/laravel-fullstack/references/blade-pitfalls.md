# Blade Pitfalls

Common Blade template errors and their fixes.

## `{!! !!}` Arrow Function String Escaping

When building JS data inside `{!! !!}` using `->map(fn($d) => ...)`, be careful with PHP string quote nesting. **Double quotes inside the Blade expression cause PHP to not evaluate variables:**

```blade
{{-- WRONG — $d->year is rendered as literal ".$d->year." --}}
const labels = [{!! $items->map(fn($d) => '"'.$d->month.' ".$d->year."\'"')->join(',') !!}];
// Output: const labels = ["JAN ".".$d->year."'",...];

{{-- CORRECT — use single quotes in PHP, double quotes for JS --}}
const labels = [{!! $items->map(fn($d) => "'".$d->month." ".$d->year."'")->join(',') !!}];
// Output: const labels = ['JAN 2025','FEB 2025',...];
```

**Rule:** Inside `{!! !!}` with arrow functions, use PHP single quotes `'...'` to wrap the JS string, and concat variables with `.`. This way PHP evaluates the variable and JS gets a valid single-quoted string.

## `@section` vs `@push` for Scripts — Critical Mismatch

**Layout defines HOW child views inject scripts:**
- If layout has `@yield('scripts')` → child uses `@section('scripts') ... @endsection`
- If layout has `@stack('scripts')` → child uses `@push('scripts') ... @endpush`

**Wrong pairing = scripts silently not loaded:**
```blade
{{-- LAYOUT has @stack('scripts') --}}
@stack('scripts')

{{-- CHILD uses @section (WRONG) --}}
@section('scripts')
    <script>console.log('never runs');</script>
@endsection
{{-- Result: JS never executes, no error, just silent failure --}}

{{-- CHILD uses @push (CORRECT) --}}
@push('scripts')
    <script>console.log('runs!');</script>
@endpush
```

**How to check layout:** `grep -n '@yield\|@stack' resources/views/layouts/app.blade.php`

**Symptom:** AJAX button click does nothing, inline JS functions undefined — but no PHP/Blade error. This means the `<script>` block was never rendered to the page.

## `@endsection` / `@endpush` Boundary Tracking

When patching Blade files that use both `@section` and `@push` blocks, an orphan `@endsection` causes: `"Cannot end a section without first starting one."`

**Typical structure:**
```
@extends('layouts.app')
@section('content')
    ... HTML ...
@endsection

@push('scripts')
    <script>...</script>
@endpush
```

**Common mistake when inserting code:** Adding HTML after `@endsection` but before `@endpush`, then accidentally adding an extra `@endsection` at the end. Always verify section balance with:

```bash
grep -n '@section\|@endsection\|@push\|@endpush' file.blade.php
```

**When inserting modal HTML + scripts into an existing file:**
- If the file has `@push('scripts')`, put modal trigger JS inside the `@push` block
- Don't add a second `@endsection` — there should be exactly one per `@section`
- The modal HTML itself goes inside `@section('content')`, the trigger JS goes in `@push('scripts')`

## Dual `class=""` Attribute Bug

HTML ignores duplicate attributes — only the FIRST one applies. This pattern appears frequently and causes classes to silently not render:

```blade
{{-- WRONG — second class="" is ignored by HTML --}}
<span class="text-sm mt-1" class="text-gray-500">text</span>
{{-- Only "text-sm mt-1" applies, "text-gray-500" is SILENTLY IGNORED --}}

{{-- CORRECT — merge into single class attribute --}}
<span class="text-sm mt-1 text-gray-500">text</span>
```

**Detection:** `grep -n 'class="[^"]*" class="' file.blade.php` — find any element with two class attributes.

**Common in this codebase:** Present in `items/index.blade.php`, `items/show.blade.php`, `defects/index.blade.php`. When patching these files, merge any duplicate class attributes.

## JS `querySelector` vs `cells[index]` in Print/Export

When extracting table cell data for print windows, CSV export, or any JS-side processing from Blade-rendered tables, **never use `querySelector` by class name** if multiple cells share the same class:

```js
// WRONG — .truncate matches first occurrence (could be wrong column)
const komentar = row.querySelector('.truncate')?.textContent;

// CORRECT — cells[index] is deterministic
const komentar = row.cells[10]?.textContent?.trim() || '';
```

Common trap: Description and Komentar both use `class="truncate"` — `querySelector('.truncate')` always returns Description regardless of which column you intended.

## Duplicate Flash Notifications

If your layout has a toast system that auto-shows `session('success')`/`session('error')` via JS (common pattern in app.blade.php), **do NOT add inline flash messages in child views** — you'll get duplicate notifications.

```blade
{{-- WRONG in child view — duplicates the toast from layout --}}
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- CORRECT — rely on layout's toast system, no inline flash needed --}}
{{-- Just return back()->with('success', 'Message') from controller --}}
```

**Detection:** If user reports "duplicate notifications" after a form submit, check if the view has inline flash HTML AND the layout has a toast trigger like:
```js
@if(session('success'))
    showToast('{{ session('success') }}', 'success');
@endif
```

**Fix:** Remove inline flash from the child view. The layout already handles it.

## Token/API Key Display — Trailing Whitespace + Copy Button

When displaying tokens, API keys, or any string meant to be copied, Blade's `{{ }}` inside a div can introduce trailing/leading whitespace:

```blade
{{-- WRONG — whitespace around {{ }} creates trailing space in textContent --}}
<div id="tokenDisplay">
    {{ $account->api_token }}
</div>
{{-- textContent = "abc123 " (trailing space!) — breaks API auth when pasted --}}

{{-- CORRECT — use data-attribute, keep Blade on same line --}}
<div id="tokenDisplay" data-token="{{ $account->api_token ?? '' }}">{{ $account->api_token ?? '' }}</div>
```

**Copy button must read from `data-token`, not `textContent`:**
```js
// WRONG — picks up whitespace from HTML formatting
const text = tokenEl.textContent.trim();

// CORRECT — data-attribute is always clean
const text = tokenEl.getAttribute('data-token');
```

**Copy button must receive `event` explicitly:**
```html
<!-- WRONG — event is undefined inside the function (not a parameter) -->
<button onclick="copyToken()">
<script>
function copyToken() {
    event.target.closest('button'); // ReferenceError in strict mode
}
</script>

<!-- CORRECT — pass event as argument -->
<button onclick="copyToken(event)">
<script>
function copyToken(event) {
    const btn = event.target.closest('button');
}
</script>
```

**Clipboard fallback** — `navigator.clipboard.writeText()` can fail in non-HTTPS or iframe contexts. Add fallback:
```js
navigator.clipboard.writeText(text).then(() => {
    // success feedback
}).catch(() => {
    // Fallback: select text in element
    const range = document.createRange();
    range.selectNodeContents(tokenEl);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
    document.execCommand('copy');
    sel.removeAllRanges();
});
```

## Debugging Checklist

When you get a Blade compilation error:
1. Run `grep -n '@section\|@endsection\|@push\|@endpush' file.blade.php` to check section balance
2. For `{!! !!}` output, `curl -s URL | grep -oP 'const xxx = \[.*?\];'` to see what PHP actually rendered
3. Check `storage/logs/laravel.log` — Blade errors include the exact view file path

## Missing CSRF Meta Tag for JS `fetch()` POST

When using `fetch()` with POST method in Blade/JS, you need `<meta name="csrf-token" content="{{ csrf_token() }}">` in the `<head>`. Without it, `document.querySelector('meta[name="csrf-token"]')` returns null and the request fails with a 419.

**Check:** `grep -r 'csrf-token' resources/views/layouts/` — if only found in JS (not in `<head>`), add the meta tag.

```blade
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    ...
</head>
```

## Tinker `--execute` Fails on Complex PHP

`php artisan tinker --execute="..."` with complex code (closures, multiline, nested quotes) causes psysh parse errors. Workaround: write a temp `.php` file that bootstraps Laravel, run with `php file.php`, then delete.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Your complex logic here...
```
