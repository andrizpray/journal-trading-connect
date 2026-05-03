# 📈 Journal Trading Connect

**Import & Analisis Riwayat Trading dari MT4/MT5**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)

## 🚀 Features

### 📂 **CSV Import**
- Import trade history dari MT4/MT5 (format CSV)
- Auto-detect format CSV (fleksibel nama kolom)
- Skip duplikat trade yang sudah diimport
- Auto kalkulasi result (win/loss/break even)
- Auto hitung durasi trade
- Download template CSV

### 💼 **Trading Accounts**
- Kelola multi akun trading (MT4/MT5)
- Statistik per akun (total trade, P&L)
- Tracking last sync

### 📊 **Analytics Dashboard**
- Total akun, total trade, total P&L, win rate
- Chart P&L 30 hari terakhir (Chart.js)
- Recent trades table

### 📈 **Trade History**
- Riwayat trade lengkap
- Filter: akun, pair, hasil, date range
- Pagination 50 per halaman

### 📓 **Trading Journal**
- CRUD journal entry manual
- Auto-create journal dari import CSV
- Emotion score, analisis, pelajaran, strategi
- Link ke trade history

## 🛠️ Tech Stack

**Backend:** Laravel 11.x, PHP 8.3, MySQL 8.0
**Frontend:** Blade, Tailwind CSS 3.4, Alpine.js 3.14, Chart.js 4.4, Font Awesome 6.4
**Packages:** Maatwebsite Excel 3.1

## 📦 Installation

```bash
git clone https://github.com/andrizpray/journal-trading-connect.git
cd journal-trading-connect
composer install
npm install --legacy-peer-deps
cp .env.example .env
php artisan key:generate
# Configure DB in .env
php artisan migrate
npm run build
php artisan serve
```

## 👥 Author

- **Andriz** — [@andrizpray](https://github.com/andrizpray)
