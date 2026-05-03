# Journal Trading Connect — Roadmap Pengembangan

> **Repo:** github.com/andrizpray/journal-trading-connect  
> **Stack:** Laravel 13 + Blade + Tailwind + Alpine.js + Chart.js + Font Awesome + Maatwebsite Excel  
> **VPS:** 43.134.37.14, port 8081, 1.9GB RAM

---

## 📊 Phase 1 — Stabilisasi & Perbaikan UX (MVP Polish)
*Target: app stabil, nyaman dipakai daily*

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 1.1 | **Fix font-awesome 4 → 6** | package.json masih `font-awesome: ^4.7.0`, upgrade ke `@fortawesome/fontawesome-free: ^6.5` dan update semua icon reference |
| 1.2 | **Export trade history ke CSV/Excel** | Tombol export di halaman Riwayat Trade, filter ikut ke export |
| 1.3 | **Import history log** | Tabel riwayat import (tanggal, file, jumlah trade imported, akun, status) supaya user tahu kapan terakhir sync |
| 1.4 | **Edit & hapus trade** | Saat ini trade read-only setelah import. Tambah aksi edit/hapus per trade |
| 1.5 | **Dashboard per-akun filter** | Dropdown/selector di dashboard untuk filter stats berdasarkan akun trading tertentu |
| 1.6 | **Toast notification** | Success/error message pakai toast (auto-dismiss) bukan flash only |
| 1.7 | **Responsive polish** | Test semua halaman di mobile 375px, fix zoom/overflow issue |
| 1.8 | **Loading state** | Spinner/skeleton saat import CSV sedang proses (bukan form freeze) |

---

## 📈 Phase 2 — Analytics & Insight
*Target: user bisa analisis performa trading*

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 2.1 | **Halaman Analytics** | Halaman baru dengan statistik mendalam: win rate per pair, profit per hari/minggu/bulan, rata-rata durasi trade, best/worst pair |
| 2.2 | **Chart performa per pair** | Bar chart P&L per currency pair (top pairs) |
| 2.3 | **Chart equity curve** | Garis equity berdasarkan cumulative P&L dari awal |
| 2.4 | **Heatmap trading** | Tabel heatmap: hari × jam (kapan paling sering trade, jam berapa paling profit) |
| 2.5 | **Streak tracker** | Win streak & lose streak terpanjang |
| 2.6 | **Risk metrics** | Max drawdown, avg win vs avg loss, profit factor, R:R ratio |
| 2.7 | **Perbandingan akun** | Chart/tafel perbandingan P&L antar akun trading |

---

## 📝 Phase 3 — Journal & Review System
*Target: journal trading yang powerful*

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 3.1 | **Rich text journal** | Quill/TinyMCE editor untuk analisis (bold, list, image attach) |
| 3.2 | **Screenshot/attachment** | Upload screenshot chart ke journal entry |
| 3.3 | **Tag & kategori** | Tag journal (breakout, scalping, news, dll) + filter by tag |
| 3.4 | **Trading plan template** | Template pre-trade plan: setup, entry, SL, TP, reasoning |
| 3.5 | **Weekly/Monthly review** | Auto-generate ringkasan: total trade, win rate, top lesson, emotion trend |
| 3.6 | **Journal search** | Pencarian full-text di semua journal entry |

---

## 🔗 Phase 4 — Integrasi & Connect
*Target: lebih dari sekadar CSV import*

| # | Fitur | Deskripsi | Status |
|---|-------|-----------|--------|
| 4.1 | **MetaAPI.cloud integration** | Connect akun MT4/MT5 real-time via MetaAPI (read-only) | ⏳ Dikembangkan nanti |
| 4.2 | **Auto-sync schedule** | Cron job sync otomatis setiap jam/hari dari MetaAPI | ⏳ Dikembangkan nanti |
| 4.3 | **Multiple CSV format** | Support TradingView, cTrader, DXtrade CSV selain MT4/MT5 | ✅ Selesai |
| 4.4 | **Copy trade dari Jurnal Trading** | Import trade dari project jurnal-trading (port 80) via API | ✅ Selesai |
| 4.5 | **Webhook notification** | Notif ke Telegram saat trade import selesai, daily summary | ⏳ Dikembangkan nanti |

---

## 👥 Phase 5 — Multi-user & Kolaborasi
*Target: siap untuk multiple user*

| # | Fitur | Deskripsi | Status |
|---|-------|-----------|--------|
| 5.1 | **Role system** | Admin (lihat semua) + User (hanya data sendiri) | ✅ Selesai |
| 5.2 | **Leaderboard** | Ranking win rate antar user (opt-in) | ✅ Selesai |
| 5.3 | **Public profile** | Share stats publik via link (pilih apa yang visible) | ✅ Selesai |

---

## 📱 Phase 6 — Mobile & PWA
*Target: akses dari HP nyaman*

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 6.1 | **PWA manifest** | Installable di homescreen, offline splash screen | ✅ Selesai |
| 6.2 | **Service worker** | Cache halaman utama untuk akses offline | ✅ Selesai |
| 6.3 | **Mobile-optimized journal** | Quick-add trade via mobile (minimal form) | ✅ Selesai |
| 6.4 | **Push notification** | Reminder daily journal, weekly review (infra, needs HTTPS) | ✅ Selesai |

---

## 🔒 Phase 7 — Security & Production Ready
*Target: siap production*

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 7.1 | **Rate limiting** | Throttle login, import, API calls |
| 7.2 | **2FA (Two-Factor Auth)** | Google Authenticator / TOTP |
| 7.3 | **Activity log** | Log semua aksi user (login, import, delete, export) |
| 7.4 | **Data backup** | Auto-backup DB harian ke file |
| 7.5 | **HTTPS/SSL** | Let's Encrypt untuk 8081 atau subdomain |
| 7.6 | **Email verification** | Aktifkan fitur email verify dari Breeze |
| 7.7 | **Queue jobs** | Import CSV via queue (supaya tidak timeout untuk file besar) |

---

## 💰 Phase 8 — Monetisasi (Opsional)
*Target: siap jadi produk*

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 8.1 | **Subscription system** | Free (1 akun, 100 trade/bulan) vs Pro (unlimited, analytics, export) |
| 8.2 | **Payment gateway** | Midtrans/Xendit untuk payment IDR |
| 8.3 | **Landing page** | Public marketing page dengan CTA |
| 8.4 | **Referral system** | Referral code untuk bonus subscription |

---

## 🗓️ Prioritas Rekomendasi

**Sekarang (minggu ini):**
- 1.1 Fix font-awesome
- 1.4 Edit/hapus trade
- 1.7 Responsive polish

**2 minggu ke depan:**
- 1.2 Export CSV
- 1.8 Loading state
- 2.1 Halaman Analytics
- 2.3 Equity curve

**1 bulan ke depan:**
- 2.2, 2.4, 2.5 Chart & metrics
- 3.1 Rich text journal
- 3.4 Trading plan template

**Nanti (sesuai demand):**
- Phase 4 (integrasi) jika ada user yang minta
- Phase 5+ setelah base stabil

---

*Diperbarui: 3 Mei 2026*
