# SB Admin 2 Dark Theme Integration (GitHub Dark Palette)

Complete pattern for integrating SB Admin 2 (Bootstrap 4) into Laravel Blade with a dark theme inspired by GitHub's dark mode.

## Asset Installation

```bash
git clone --depth 1 https://github.com/StartBootstrap/startbootstrap-sb-admin-2.git /tmp/sb2
PROJECT=/path/to/laravel/project

cp -r /tmp/sb2/vendor $PROJECT/public/    # Bootstrap 4, jQuery, Chart.js, FA5, DataTables, jQuery Easing
cp -r /tmp/sb2/css $PROJECT/public/        # sb-admin-2.min.css
cp -r /tmp/sb2/js $PROJECT/public/          # sb-admin-2.min.js + demo chart scripts
cp -r /tmp/sb2/img $PROJECT/public/         # undraw SVG illustrations (optional)
```

**Includes:** Bootstrap 4.6, jQuery 3.5.1, Chart.js 2.9.4, Font Awesome 5.15, DataTables, jQuery Easing.

## Layout Structure (`layouts/app.blade.php`)

```
Page Wrapper (#wrapper)
├── Sidebar (ul.navbar-nav.bg-gradient-primary.sidebar.sidebar-dark.accordion)
│   ├── Sidebar Brand (link to home)
│   ├── Divider
│   ├── Nav Items (li.nav-item > a.nav-link)
│   │   └── Active state: add 'active' class
│   ├── Sidebar Toggler (button#sidebarToggle)
│   └── [Optional: Sidebar Card for CTA]
├── Content Wrapper (#content-wrapper)
│   ├── Main Content (#content)
│   │   ├── Topbar (nav.navbar.navbar-light.topbar)
│   │   │   ├── Sidebar Toggle (mobile)
│   │   │   ├── Page Title
│   │   │   └── Topbar Navbar (alerts, messages, user)
│   │   └── Page Content (.container-fluid)
│   │       └── @yield('content')
│   └── Footer (footer.sticky-footer)
└── Scroll to Top Button
```

**Script includes (bottom of body):**
```html
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>
@stack('scripts')
```

**CSS includes (head):**
```html
<link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
<link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
```

## Dark Theme CSS Overrides

Copy this block into `<style>` in the layout. Overrides SB Admin 2's light defaults to GitHub's dark palette:

```css
body { background-color: #0d1117; color: #c9d1d9; }
#content-wrapper { background-color: #0d1117; }
.bg-white, .card, .topbar, footer.sticky-footer { background-color: #161b22 !important; }
.text-gray-800 { color: #c9d1d9 !important; }
.text-gray-900 { color: #f0f6fc !important; }
.text-gray-600 { color: #8b949e !important; }
.text-gray-500 { color: #8b949e !important; }
.text-gray-300 { color: #c9d1d9 !important; }
.text-gray-400 { color: #8b949e !important; }
.text-gray-100 { color: #f0f6fc !important; }
.border-bottom { border-color: #30363d !important; }
.table-bordered { border-color: #30363d !important; }
.table-bordered td, .table-bordered th { border-color: #30363d !important; }
.table thead th { background-color: #1c2128; color: #c9d1d9; border-bottom: 2px solid #30363d; }
.table td { color: #c9d1d9; border-top: 1px solid #21262d; }
.table-hover tbody tr:hover { background-color: #1c2128; }
.form-control, .custom-select {
    background-color: #0d1117; color: #c9d1d9; border-color: #30363d;
}
.form-control:focus, .custom-select:focus {
    background-color: #0d1117; color: #c9d1d9;
    border-color: #58a6ff; box-shadow: 0 0 0 0.2rem rgba(88,166,255,0.25);
}
.form-control::placeholder { color: #484f58; }
.input-group-append .btn { border-color: #30363d; }
.card-header { background-color: #1c2128; border-bottom: 1px solid #30363d; }
.card-body { background-color: #161b22; }
.dropdown-menu { background-color: #1c2128; border-color: #30363d; }
.dropdown-item { color: #c9d1d9; }
.dropdown-item:hover { background-color: #21262d; }
.page-link { background-color: #161b22; color: #58a6ff; border-color: #30363d; }
.page-link:hover { background-color: #1c2128; color: #79c0ff; }
.page-item.active .page-link { background-color: #238636; border-color: #238636; color: #fff; }
.page-item.disabled .page-link { background-color: #161b22; color: #484f58; }
.btn-primary { background-color: #238636; border-color: #238636; }
.btn-primary:hover { background-color: #2ea043; }
.btn-outline-primary { color: #58a6ff; border-color: #30363d; }
.btn-outline-primary:hover { background-color: #1c2128; color: #79c0ff; }
.border-left-primary { border-left: 0.25rem solid #58a6ff !important; }
.border-left-success { border-left: 0.25rem solid #238636 !important; }
.border-left-info { border-left: 0.25rem solid #39d2c0 !important; }
.border-left-warning { border-left: 0.25rem solid #d29922 !important; }
.border-left-danger { border-left: 0.25rem solid #f85149 !important; }
.text-primary { color: #58a6ff !important; }
.text-success { color: #238636 !important; }
.text-info { color: #39d2c0 !important; }
.text-warning { color: #d29922 !important; }
.text-danger { color: #f85149 !important; }
.bg-light { background-color: #1c2128 !important; }
.shadow { box-shadow: 0 0.15rem 1.75rem 0 rgba(0,0,0,0.3) !important; }
.topbar { background-color: #161b22 !important; }
footer.sticky-footer { border-top: 1px solid #30363d; }
.sidebar .nav-link { color: rgba(255,255,255,0.8); }
.sidebar .nav-link:hover { color: #fff; }
.sidebar .nav-link.active { color: #fff; }
.sidebar-heading { color: rgba(255,255,255,0.4); }
```

## Chart.js Dark Configuration

SB Admin 2 includes Chart.js 2.9. All charts need dark-aware axis/grid colors:

```javascript
const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    scales: {
        y: {
            ticks: { color: '#8b949e' },
            grid: { color: '#21262d' }
        },
        x: {
            ticks: { color: '#8b949e' },
            grid: { color: '#21262d' }
        }
    },
    plugins: {
        legend: {
            labels: { color: '#c9d1d9', padding: 15, font: { size: 11 } }
        }
    }
};

// For doughnut/pie — legend at bottom
const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: { position: 'bottom', labels: { color: '#c9d1d9', padding: 15, font: { size: 11 } } }
    }
};
```

**Chart color palette (GitHub-inspired):**
```javascript
const colors = ['#58a6ff', '#238636', '#d29922', '#f85149', '#39d2c0', '#bc8cff', '#ff7b72', '#79c0ff', '#56d364', '#e3b341'];
```

**Passing data from Blade controller** — pre-compute arrays in controller, use `{!! !!}` for JSON in Blade:
```blade
<script>
const labels = [{!! $data->pluck('name')->map(fn($n) => '"' . addslashes($n) . '"')->join(',') !!}];
const values = [{!! $data->pluck('count')->join(',') !!}];
</script>
```

## Custom Pagination Template

Copy to `resources/views/vendor/pagination/custom.blade.php` (Bootstrap 4 compatible):

```blade
@if ($paginator->hasPages())
<nav>
    <ul class="pagination">
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a></li>
        @else
            <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
        @endif
    </ul>
</nav>
@endif
```

Usage in views: `{{ $items->links('vendor.pagination.custom') }}`

## Common Component Patterns

### Stat Cards (border-left colored)
```html
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Label</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-100">{{ number_format($value) }}</div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-icon fa-2x text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Filter Card with Dropdowns
```html
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="text-gray-500 text-xs font-weight-bold">Label</label>
                <select name="field" class="form-control custom-select">
                    <option value="">Semua</option>
                    @foreach($options as $opt)
                        <option value="{{ $opt }}" {{ request('field') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
```

### Data Table
```html
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-table mr-2"></i>Title</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Col 1</th>
                        <th>Col 2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->field }}</td>
                        <td>{{ $item->field2 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links('vendor.pagination.custom') }}
    </div>
</div>
```

### Status Badges
```css
.badge-good { background-color: #238636; }
.badge-hold { background-color: #d29922; }
.badge-problem { background-color: #f85149; }
.badge-na { background-color: #484f58; }
```

## CSS Variables Approach (Recommended over Hardcoded Overrides)

Using CSS custom properties is more maintainable than repeating hardcoded hex values. Define all theme tokens as `:root` variables:

```css
:root {
    --bg-primary: #0d1117;
    --bg-card: #161b22;
    --bg-card-header: #1c2128;
    --bg-input: #0d1117;
    --border: #30363d;
    --text-primary: #f0f6fc;
    --text-secondary: #c9d1d9;
    --text-muted: #8b949e;
    --text-dim: #484f58;
    --accent-blue: #58a6ff;
    --accent-green: #238636;
    --accent-teal: #39d2c0;
    --accent-yellow: #d29922;
    --accent-red: #f85149;
    --accent-purple: #bc8cff;
    --accent-orange: #ff7b72;
    --hover-bg: #1c2128;
}
```

Then reference variables in all overrides: `background-color: var(--bg-card)`, `color: var(--text-secondary)`, etc. This makes theme switching and adjustments trivial.

## Mobile-Responsive Patterns

### Desktop Table → Mobile Card View

Tables with many columns are unusable on mobile. Use a dual-render pattern:

```blade
{{-- Desktop table (hidden on mobile) --}}
<div class="desktop-table" style="display:block;">
    <div class="card shadow"><div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size:0.8rem;">
                <thead><tr><th>Lot ID</th><th>GSM</th><th>Location</th></tr></thead>
                <tbody>
                    @foreach($items as $item)
                    <tr onclick="window.location='{{ route('items.show', $item->id) }}'">
                        <td><a href="..." class="text-primary font-weight-bold">{{ $item->lot_id }}</a></td>
                        <td>{{ $item->gsm }}</td>
                        <td><span class="badge badge-loc">{{ $item->location }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
</div>

{{-- Mobile card view (hidden on desktop) --}}
<div class="mobile-cards" style="display:none;">
    @foreach($items as $item)
    <a href="{{ route('items.show', $item->id) }}" class="text-decoration-none">
        <div class="mobile-item-card">
            <span class="lot-id"><i class="fas fa-barcode mr-1"></i>{{ $item->lot_id }}</span>
            <div class="info-row"><span class="info-label">GSM</span><span class="info-value">{{ $item->gsm }}</span></div>
            <div class="loc-tags">
                <span class="loc-tag"><i class="fas fa-map-pin mr-1"></i>{{ $item->location }}</span>
            </div>
        </div>
    </a>
    @endforeach
</div>

{{-- Toggle CSS --}}
<style>
.mobile-cards { display: none; }
.desktop-table { display: block; }
@media (max-width: 768px) {
    .desktop-table { display: none; }
    .mobile-cards { display: block; }
}
.mobile-item-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: 8px; padding: 14px; margin-bottom: 10px;
    transition: border-color 0.2s;
}
.mobile-item-card:hover { border-color: var(--accent-blue); }
.mobile-item-card .lot-id { color: var(--accent-blue); font-weight: 700; font-size: 0.95rem; display: block; margin-bottom: 8px; }
.mobile-item-card .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.8rem; }
.mobile-item-card .info-label { color: var(--text-muted); }
.mobile-item-card .info-value { color: var(--text-secondary); font-weight: 600; text-align: right; max-width: 60%; }
.mobile-item-card .loc-tags { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 4px; }
.loc-tag { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; background: rgba(88,166,255,0.1); color: var(--accent-blue); border: 1px solid rgba(88,166,255,0.2); }
</style>
```

### Collapsible Advanced Filters

Mobile screens can't fit 6+ dropdowns at once. Use Bootstrap collapse with auto-show when filters are active:

```blade
<div class="card shadow mb-3">
    <a class="card-header py-2 d-flex align-items-center justify-content-between text-decoration-none"
       data-toggle="collapse" href="#advancedFilters" style="cursor:pointer;">
        <h6 class="m-0 font-weight-bold text-primary" style="font-size:0.85rem;">
            <i class="fas fa-sliders-h mr-1"></i>Filter Lanjutan
        </h6>
        <i class="fas fa-chevron-down text-gray-500" style="font-size:0.75rem;"></i>
    </a>
    <div class="collapse {{ (request('paper_type') || request('gsm')) ? 'show' : '' }}" id="advancedFilters">
        <div class="card-body pt-2 pb-3">
            {{-- Filter dropdowns here --}}
        </div>
    </div>
</div>
```

### Filter Bar Mobile Adaptation

```css
@media (max-width: 768px) {
    .filter-row .col-lg-3, .filter-row .col-lg-2, .filter-row .col-md-6, .filter-row .col-md-4 {
        width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important;
        margin-bottom: 8px !important;
    }
}
```

## Interactive Elements

### Clickable Table Rows

Make entire table row navigate to detail page:
```blade
<tr onclick="window.location='{{ route('items.show', $item->id) }}'" style="cursor:pointer;">
```

### Card Hover Lift Effect
```html
<div class="card shadow" style="cursor:pointer; transition: transform 0.15s;"
     onmouseover="this.style.transform='translateY(-2px)'"
     onmouseout="this.style.transform='none'">
```

### Clickable Dashboard Stats → Filtered List
```blade
<a href="{{ route('items.index', ['receiving_2026' => $loc->receiving_2026]) }}" class="text-decoration-none">
    <div class="card border-left-info shadow stat-card">...</div>
</a>
```

### Custom Scrollbar (Dark Theme)
```css
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-dim); }
```

## Location Tracking Timeline

For inventory/warehouse apps where items move through stages (SO → Receiving → PIC → Next SO):

```blade
<div class="loc-timeline">
    @if($item->so_desember && $item->so_desember != '-')
    <div class="loc-timeline-item">
        <div class="loc-period">SO Desember 2025</div>
        <div class="loc-value"><i class="fas fa-file-invoice mr-1" style="color:var(--accent-purple);"></i>{{ $item->so_desember }}</div>
    </div>
    @endif
    @if($item->receiving_2026 && $item->receiving_2026 != '-')
    <div class="loc-timeline-item">
        <div class="loc-period">Receiving 2026</div>
        <div class="loc-value"><i class="fas fa-warehouse mr-1" style="color:var(--accent-blue);"></i>{{ $item->receiving_2026 }}</div>
    </div>
    @endif
    {{-- More stages... --}}
</div>

<style>
.loc-timeline { position: relative; padding-left: 20px; }
.loc-timeline::before { content: ''; position: absolute; left: 6px; top: 8px; bottom: 8px; width: 2px; background-color: var(--border); }
.loc-timeline-item { position: relative; padding: 8px 0 8px 20px; }
.loc-timeline-item::before { content: ''; position: absolute; left: -18px; top: 14px; width: 10px; height: 10px; border-radius: 50%; background-color: var(--accent-blue); border: 2px solid var(--bg-card); }
.loc-timeline-item .loc-period { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; }
.loc-timeline-item .loc-value { font-size: 0.9rem; color: var(--text-primary); font-weight: 600; margin-top: 2px; }
</style>
```

## Info Grid for Detail Pages

Responsive grid layout for showing key-value pairs:

```blade
<div class="info-grid">
    <div class="info-cell">
        <div class="info-label"><i class="fas fa-weight-hanging mr-1"></i>End Qty</div>
        <div class="info-value" style="font-size:1.2rem; color:var(--accent-green);">{{ number_format($item->end_qty) }}</div>
    </div>
    <div class="info-cell">
        <div class="info-label"><i class="fas fa-scroll mr-1"></i>Paper Type</div>
        <div class="info-value">{{ $item->paper_type ?? '-' }}</div>
    </div>
</div>

<style>
.info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
@media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
.info-cell { background-color: var(--bg-card-header); border-radius: 6px; padding: 12px; border: 1px solid var(--border); }
.info-cell .info-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.info-cell .info-value { font-size: 0.95rem; color: var(--text-primary); font-weight: 600; }
</style>
```

## Badge Types

```css
.badge-good { background-color: var(--accent-green); color: #fff; }
.badge-hold { background-color: var(--accent-yellow); color: #000; }
.badge-problem { background-color: var(--accent-red); color: #fff; }
.badge-na { background-color: #484f58; color: #fff; }
.badge-loc { background-color: rgba(88,166,255,0.15); color: var(--accent-blue); border: 1px solid rgba(88,166,255,0.3); }
.badge-so { background-color: rgba(188,140,255,0.15); color: var(--accent-purple); border: 1px solid rgba(188,140,255,0.3); }
```

Use `.badge-loc` for location badges, `.badge-so` for SO/order reference badges — visual distinction from status badges.

## Tailwind CSS Coexistence with SB Admin 2 (Bootstrap 4)

SB Admin 2 uses Bootstrap 4, which conflicts with Tailwind's CSS reset (preflight). To use both frameworks in the same app without conflicts, add Tailwind via CDN with two critical config overrides:

**Add to `<head>` in layout (BEFORE sb-admin-2.min.css):**

```html
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: { preflight: false },
        prefix: 'tw-'
    }
</script>
<link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
```

**Two config keys are essential:**

1. **`preflight: false`** — disables Tailwind's CSS reset (normalize/reboot) which would override Bootstrap 4's base styles (margins, paddings, font sizes, form styles). Without this, Bootstrap sidebar, dropdowns, and form controls break.

2. **`prefix: 'tw-'`** — all Tailwind classes require the `tw-` prefix (e.g., `tw-flex`, `tw-grid`, `tw-text-white`). This prevents class name collisions between Bootstrap and Tailwind (e.g., `container`, `hidden`, `flex`, `border`).

**Usage pattern:** Use Bootstrap 4 classes for SB Admin 2 structural elements (sidebar, topbar, cards, tables, forms) and Tailwind (`tw-` prefix) for modern dashboard elements (gradient cards, responsive grids, utilities):

```blade
{{-- SB Admin 2 structure --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">...</div>
    <div class="card-body">
        {{-- Tailwind for modern dashboard layout --}}
        <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4">
            <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-purple-600 tw-rounded-xl tw-p-4">
                <div class="tw-text-blue-200 tw-text-xs tw-font-semibold">Total Items</div>
                <div class="tw-text-white tw-text-2xl tw-font-bold">{{ $total }}</div>
            </div>
        </div>
    </div>
</div>
```

**Pitfall:** Order matters — Tailwind CDN script must load BEFORE Bootstrap CSS. If Bootstrap loads first, Tailwind's non-preflight utilities may still override some Bootstrap styles unpredictably.

**Pitfall:** The `tw-` prefix applies to ALL Tailwind utilities, including responsive (`sm:tw-flex`), hover (`hover:tw-bg-blue-600`), and arbitrary values (`tw-text-[#ff6b35]`). Don't forget the prefix on every Tailwind class.

**Pitfall:** Tailwind CDN is a development tool (adds ~300KB JS). For production, consider removing it and using only Bootstrap or migrating to a pure Tailwind approach with PostCSS build.

## Model Accessor: Computed Location from Priority Columns

For inventory/warehouse apps where an item's "current location" is the first non-empty value across multiple tracking columns (checked in priority order). Instead of repeating this logic in every view/controller, use a model accessor.

**Example — RollItem with location tracking columns:**

```php
// app/Models/RollItem.php

/**
 * Get the current location by checking tracking columns in priority order.
 * Returns the first non-empty value, or null if all are empty.
 */
public function getCurrentLocation(): ?string
{
    $columns = [
        'so_desember',      // Priority 1 (most recent SO)
        'receiving_2026',   // Priority 2 (receiving warehouse)
        'so_maret_2026',    // Priority 3 (next SO)
        'pic_2026',         // Priority 4 (person in charge)
        'rcv_cnv_2026',     // Priority 5 (conversion receiving)
        'so_september',     // Priority 6 (oldest SO)
    ];

    foreach ($columns as $col) {
        $val = $this->getAttribute($col);
        if (!empty($val) && trim($val) !== '' && trim($val) !== '-') {
            return trim($val);
        }
    }

    return null;
}

/**
 * Get the current location with a label showing which source column it came from.
 * Returns "Source: value" format, e.g., "Receiving: GUDANG ROL 1".
 */
public function getCurrentLocationLabel(): string
{
    $columnLabels = [
        'so_desember'    => 'SO Desember',
        'receiving_2026' => 'Receiving',
        'so_maret_2026'  => 'SO Maret 2026',
        'pic_2026'       => 'PIC 2026',
        'rcv_cnv_2026'   => 'RCV/CNV 2026',
        'so_september'   => 'SO September',
    ];

    $columns = array_keys($columnLabels);

    foreach ($columns as $col) {
        $val = $this->getAttribute($col);
        if (!empty($val) && trim($val) !== '' && trim($val) !== '-') {
            return $columnLabels[$col] . ': ' . trim($val);
        }
    }

    return 'Belum ada lokasi';
}
```

**Usage in Blade views:**

```blade
{{-- Simple value --}}
<span class="badge badge-loc">{{ $item->current_location ?? '-' }}</span>

{{-- With source label --}}
<span class="badge badge-loc">{{ $item->current_location_label }}</span>
{{-- Output: "Receiving: GUDANG ROL 1" --}}
```

**Usage in dashboard stats (controller):**

```php
$withLocation = RollItem::all()->filter(fn($item) => $item->getCurrentLocation() !== null)->count();
$topLocations = RollItem::all()
    ->map(fn($item) => $item->getCurrentLocationLabel())
    ->filter()
    ->groupBy(fn($label) => $label)
    ->map(fn($group) => $group->count())
    ->sortDesc()
    ->take(10);
```

**Pitfall:** Accessor naming convention — `getCurrentLocation()` becomes `$item->current_location` (snake_case). Laravel converts camelCase accessors to snake_case properties. Don't use `$item->getCurrentLocation()` in Blade — use `$item->current_location`.

**Pitfall:** Checking for dash/hyphen (`trim($val) !== '-'`) is important because spreadsheet imports often use `-` or `N/A` to indicate empty. Add any sentinel values your data uses to the empty check.

## Pitfalls

- **`withTrashed()` on non-SoftDelete model:** `BadMethodCallException`. Only use `withTrashed()` if the model uses `SoftDeletes` trait. If you don't need soft deletes, just use `::query()` or `::all()`.
- **Storage permission denied:** PHP-FPM runs as `www-data`, CLI as `ubuntu`. After any permission fix, clear cache AND reload PHP-FPM: `php artisan view:clear && sudo systemctl reload php8.3-fpm`.
- **SB Admin 2 uses Bootstrap 4:** `data-toggle="dropdown"` not `data-bs-toggle="dropdown"`. jQuery required for all interactive components. Font Awesome 5 class names (not FA6 `fa-solid fa-x`).
- **Chart.js 2.9 syntax:** Uses `type: 'doughnut'` (not `'doughnut'` in v3+), `scales` config is different from Chart.js 3+.
- **`@json()` with closures:** Never put `fn()` or closures inside `@json()` in Blade. Pre-compute all chart data in the controller. Use `{!! !!}` for raw JSON output of pre-computed arrays.
- **SB Admin 2 assets are large (~1900 files).** The `vendor/` directory includes SCSS source, webfonts SVGs, and full icon sets. Commit size is large. Consider `.gitignore` for `public/vendor/*/scss/` and `public/vendor/*/webfonts/src/` if repo size matters.
