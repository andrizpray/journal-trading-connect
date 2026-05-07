# Light Theme Dashboard Design System (White + Blue)

Reference palette for admin/monitoring dashboards. Based on AdminLTE, Tabler, CoreUI, and Filament Panel conventions. Uses Tailwind CSS color classes.

## Color Palette

### Primary Blues
- `#3B82F6` — blue-500 (primary actions, links, active states)
- `#2563EB` — blue-600 (hover, focus)
- `#1D4ED8` — blue-700 (active nav text)
- `#EFF6FF` — blue-50 (active bg, hover bg, subtle tint)
- `#DBEAFE` — blue-100 (badge bg, soft highlight)

### Backgrounds
- Page bg: `#F8FAFC` (slate-50 — not pure white, reduces eye strain)
- Card bg: `#FFFFFF` (white)
- Sidebar bg: `#FFFFFF` (white)
- Table alt row: `#F8FAFC` (slate-50)
- Hover row: `#EFF6FF` (blue-50)

### Text
- Primary: `#0F172A` (slate-900)
- Secondary: `#475569` (slate-600)
- Muted/labels: `#94A3B8` (slate-400)

### Borders
- Default: `#E2E8F0` (slate-200)
- Subtle: `#F1F5F9` (slate-100)
- Focus ring: `#3B82F6` with 3px `#BFDBFE` (blue-200) offset

### Status Colors
- Success: `#10B981` (emerald-500), bg: `#ECFDF5` (emerald-50)
- Warning: `#F59E0B` (amber-500), bg: `#FFFBEB` (amber-50)
- Danger: `#EF4444` (red-500), bg: `#FEF2F2` (red-50)
- Info: `#06B6D4` (cyan-500), bg: `#ECFEFF` (cyan-50)

## Component Patterns

### Cards
- Background: white, border 1px slate-200, rounded-xl (12px)
- Shadow: `0 1px 3px rgba(0,0,0,0.06)` (very subtle, no heavy shadows)
- Padding: p-5 or p-6
- Metric cards: optional 4px left border accent in primary color

### Buttons
- Primary: bg blue-500, text white, rounded-lg (8px), font-medium
- Hover: bg blue-600
- Secondary: white bg, border slate-200, text slate-700
- Focus: border blue-500 + ring-2 blue-100

### Tables
- Header: bg slate-50, text slate-500, text-xs uppercase tracking-wide
- Border bottom: 2px slate-200
- Body rows: text slate-700, text-sm, border-b slate-100
- Hover: bg blue-50
- Status badges: rounded-full, text-xs, colored bg at 50-level

### Sidebar
- Width: 260px, white bg, right border slate-200
- Active item: bg blue-50, text blue-700
- Hover: bg slate-50
- Mobile: slide-in from left with backdrop overlay

### Gauges (Server metrics)
- SVG circular gauge, stroke-linecap round
- Colors: <60% blue, 60-80% amber, >80% red
- Disk threshold higher: >90% red

## Chart.js Theme
- Colors: blue-500, cyan-500, violet-500, emerald-500, amber-500, red-500
- Grid lines: slate-200 (#E2E8F0)
- Axis labels: slate-400
- Tooltip: slate-900 bg with white text
- Fill gradients: rgba(primary, 0.1) fading to transparent
- Bar border-radius: 6px, borderSkipped: false

## Design Principles
- No heavy drop shadows — flat with subtle 1px borders
- Generous white space — 24px gaps between cards
- Blue used sparingly for interactive elements only
- Rounded corners: 8px buttons/inputs, 12px cards, 6px badges
- Transitions: 150ms ease hovers

## ⚠️ Pitfalls

### `text-white` on White Backgrounds (Recurring Bug)
When converting from dark theme to light theme, `text-white` becomes invisible on white card backgrounds. This has caused multiple bugs across sessions. **After any view conversion or new view creation on a light theme, run:**

```bash
# Check for text-white OUTSIDE sidebar (sidebar has dark gradient so text-white is OK there)
curl -s URL | grep -o 'text-white' | wc -l
```

**Common places text-white hides:**
- Card headers / titles (`h2`, `h3`)
- Info box values (metrics, stats)
- Table row data
- Timeline content
- Comment/description text
- Stat card numbers

**Fix pattern:** Replace `text-white` with `text-gray-900` for primary text, `text-gray-700` for secondary, `text-gray-400` for muted. **Exception:** Sidebar (dark gradient bg) keeps `text-white`.

**Prevention:** When creating new views, default to `text-gray-900` (primary), `text-gray-700` (secondary), `text-gray-400` (muted/labels). Never use `text-white` outside sidebar/header-dark areas.

### `text-white` Hides in Multiple Views (Not Just the One You Changed)
When fixing `text-white` issues in one view, check ALL views in the project. The Roll Off show page (`items/show.blade.php`) was missed during earlier light theme fixes because only `defects/index.blade.php` and `items/index.blade.php` were checked. The show page had 25+ `text-white` instances across info boxes, timeline, and comments section.

**Systematic check after theme changes:**
```bash
for f in resources/views/**/*.blade.php; do
  count=$(grep -c 'text-white' "$f" 2>/dev/null || echo 0)
  if [ "$count" -gt 0 ]; then
    echo "$f: $count occurrences"
  fi
done
```
Filter out sidebar occurrences (which legitimately use `text-white` on dark gradient bg).
