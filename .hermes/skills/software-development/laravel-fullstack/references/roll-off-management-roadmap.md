# Roll Off Management — Development Roadmap

**Project:** `/home/ubuntu/roll-off-management`
**Stack:** Laravel 13 + Blade + Full Tailwind CSS (CDN) + Chart.js 4.4 + Font Awesome 6 + Inter font
**DB:** `roll_off_management` (14,875 roll items, 872 defect items)
**GitHub:** `git@github.com:andrizpray/roll-off-management.git`
**Access:** `http://43.134.37.14:8082` (internal, no auth, no domain)
**Latest commit:** `a47371c` (Defects page: add plybond, grade, comments columns + mobile Rew ID)

## Completed Phases

### Phase 1 — Foundation
- Laravel 13 project, DB setup, migrations (roll_items 21 cols, defect_items 14 cols)
- `ImportExcelData` command (chunked 500, description parsing)
- Nginx port 8082, PHP-FPM deployment

### Phase 2 — Core UI
- Layout with responsive sidebar (collapsible desktop, overlay mobile)
- RollItemController (search + 7 filters + sort + detail)
- DefectItemController (year, reason, paper_type, month, search filters)
- Mobile card views, collapsible filters, clickable rows
- Location tracking timeline, info grid detail page

### Phase 3 — Full Tailwind UI Rebuild
- Removed Bootstrap/jQuery/SB Admin 2 entirely
- Blue-white light theme: sidebar gradient biru (#1e3a5f to #1d4ed8), bg #f0f4f8, white cards
- Inter font, Font Awesome 6, Chart.js 4.4
- Design system: `.card`, `.stat-card`, `.data-table`, `.mobile-card`, `.tag-*`, `.input-field`, `.select-field`, `.btn-*`, `.timeline`, `.info-box`, `.page-btn`, `.animate-in`
- **Rule:** Secondary text uses `text-gray-400` (not `text-gray-300` — invisible on light bg)
- **Rule:** Stat card numbers use `text-gray-900` (not `text-white` — invisible on white cards)
- **Rule:** Blade `@section` block syntax for HTML content — never `@section('name', '<html>')` (escapes)

### Phase 4 — Dashboard and Tracking
- Location rekap accessors: `getCurrentLocation()`, `getCurrentLocationLabel()`, `getCurrentLocationSource()`
- Priority order: so_desember, receiving_2026, so_maret_2026, pic_2026, rcv_cnv_2026, so_september
- Dashboard: 4 stat cards, 4 charts (paper type bar, location horizontal bar, status doughnut, paper type distribution)

### Phase 5 — Quality Analytics
- Defect rate per paper type (grouped bar: defect count vs total rolls)
- Trend defect per bulan (line chart, month name to numeric sort)
- Top 10 defect reasons with percentage progress bars
- **Rule:** `{!! !!}` with arrow functions: use PHP single quotes for JS strings, `.` concat for variables

### Phase 6 — Export and Report
- 6.1 Export Roll Items to Excel (filtered, chunked 500, memory 512M)
- 6.2 Export Barang Bermasalah to Excel (filtered)
- 6.3 Summary Report (multi-sheet: ringkasan, defects, paper type) with modal year/month picker
- Export buttons respect active filters via `request()->except('page')`
- **Rule:** Route `/items/export` must be registered BEFORE `/items/{id}` to avoid 404

### Phase 7 — CRUD, Actions, and UI Refinements
- 7.1 Create/Edit/Delete roll items (shared form view, validation, delete confirm modal)
- 7.2 Edit button on detail page, "Tambah" button on list
- 7.3 Print-friendly layout (CSS `@media print` hides sidebar/header/footer)
- 7.4 Select & print: checkbox per row, "Select All", "Print Dipilih" button (opens print window with only selected rows)
- 7.5 Grade column added to table & mobile cards
- 7.6 SO Desember, SO Maret, PIC columns removed from table & mobile cards (lokasi already covers this)
- Flash messages (success/error) handled in layout

### Post-Phase 7 — Data Quality Fixes
- Re-parsed 14,874 rows (12,237 had null paper_type from old regex)
- Updated `parseDescription()` to support 37+ paper type patterns (B KRAFT, Core Board, Paper Medium, Grey Board, T/B B Kraft, Chip Board, COB Core Board, Brown Board, DUPLEX COATED, LAMINASI B KRAFT, PE03/02 B Kraft, Barrier Coating Board, Yellow Board, DK DUPLEX KRAFT, OCT BASE, etc.)
- Created `roll-off:reparse` artisan command for future re-parsing needs
- Fixed CORE BORAD typo → CORE BOARD (post-processing normalization in parser + DB update)
- Added grade multi-select filter (checkbox pills UI, `whereIn()` for multi-value query)
- Added comments column to items table (desktop + mobile cards) with comment-dots icon
- Fixed show page text visibility: all `text-white` → `text-gray-900`, comments bg dark→light
- Added DefectItem → RollItem relation (`belongsTo` via `lot_id`) for cross-reference grade/comments
- Defects page columns synced with items page: added Plybond, Grade (from roll item), Komentar (with icon), mobile Rew ID
- Eager load `rollItem` on paginated defects via `$defects->loadMissing(['rollItem'])`

## Planned Phases

### Phase 8 — Enhancement
- 8.1 Dark/Light theme toggle
- 8.2 Barcode/QR per roll item
- 8.3 Smart Sync Import Excel (upload, sync: add new, update changed locations, delete removed, update defects)
- 8.4 Notifikasi: item tanpa lokasi, defect baru

## Key Technical Notes

- **DefectItem model does NOT use SoftDeletes** — never call `withTrashed()` on it
- **`location_id` column is legacy** (all = "LOC03") — use `getCurrentLocation()` accessor instead
- **Description parsing:** Format is `{Paper Type} {GSM_PREFIX+GSM} E{PLYBOND} {WIDTH}`. Examples: "B KRAFT BK125 E150 690", "Core Board CO300 E200 600", "DUPLEX COATED DR0250 E150 350", "T/B B Kraft BKTB300 E150 850". GSM prefixes: BK, CO, MP, GB, CB, BB, BKTB, COB, DR, BKL, BPTBG, BRPG, BCB, BKN, BKP, BKE, BKTBS, BKW, NRPG, MPC, OCT, OCTN, DK, YB, DOCT, DRP, etc. Plybond is always E{number} in the middle. Width is the last number. 37+ paper type patterns supported via ordered prefix matching (longest first).
- **Defect reasons:** 21 categories (Kena Batu, Roll Basah, Trim Rusak, BROKE, etc.)
- **Defect data split:** 498 from 2025, 374 from 2026
- **Storage permissions:** `www-data:www-data 775` on storage/ and bootstrap/cache/
- **Every update then commit + push to GitHub** (user preference for backup)
- **No auth** — internal tool, akses via IP port only
- **No Bootstrap/jQuery** — pure Tailwind CSS, vanilla JS
- **Blue-white light theme** — user prefers this over dark theme
- **Lokasi tracking columns:** so_desember, receiving_2026, so_maret_2026, pic_2026, rcv_cnv_2026, so_september

## File Structure

```
app/
  Console/Commands/ImportExcelData.php      (chunked import, 37+ paper type patterns)
  Console/Commands/ReparseDescriptions.php   (re-parse null paper_type rows)
  Exports/RollItemsExport.php      (Phase 6: filtered, chunked, memory 512M)
  Exports/DefectItemsExport.php     (Phase 6: filtered)
  Exports/SummaryReportExport.php   (Phase 6: multi-sheet with inner sheet classes)
  Http/Controllers/DashboardController.php
  Http/Controllers/RollItemController.php    (CRUD + export)
  Http/Controllers/DefectItemController.php  (analytics + export + summary)
  Models/RollItem.php               (fillable 21 fields + location accessors)
  Models/DefectItem.php             (fillable 14 fields, NO SoftDeletes, belongsTo RollItem via lot_id)
resources/views/
  layouts/app.blade.php             (sidebar, topbar, print CSS, flash messages)
  dashboard.blade.php               (stat cards + 4 charts)
  items/index.blade.php             (table + cards + filters + export + select/print)
  items/show.blade.php              (info grid + timeline + edit/print)
  items/form.blade.php              (shared create/edit form)
  defects/index.blade.php           (analytics + table + export + summary modal)
  vendor/pagination/custom.blade.php
```
