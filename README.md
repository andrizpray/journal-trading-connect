# 📈 Journal Trading Connect

**Import, analisis, dan journaling riwayat trading dari MT4/MT5**

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat)](LICENSE)

> 🌐 **Live:** [eatrade-journal.site](https://eatrade-journal.site)

---

## 🚀 Fitur

### 📂 CSV Import
- Import trade history dari MT4/MT5 (format CSV)
- Auto-detect format CSV (fleksibel nama kolom)
- Skip duplikat trade yang sudah diimport
- Auto kalkulasi result (win/loss/break even)
- Auto hitung durasi trade
- Download template CSV
- Import history log (tanggal, file, jumlah trade, status)

### 💼 Trading Accounts
- Kelola multi akun trading (MT4/MT5)
- Statistik per akun (total trade, P&L, win rate)
- Tracking last sync
- Currency & quote decimals per akun
- API token untuk integrasi EA Logger

### 📊 Dashboard & Analytics
- Total akun, total trade, total P&L, win rate
- Chart P&L 30 hari terakhir (Chart.js)
- Filter stats per akun trading
- Chart performa per pair
- Equity curve (cumulative P&L)
- Heatmap trading (hari × jam)
- Best/worst pair, streak tracker

### 📈 Trade History
- Riwayat trade lengkap dengan CRUD
- Filter: akun, pair, hasil, date range
- Pagination 50 per halaman
- Export ke CSV/Excel (Maatwebsite Excel)

### 📓 Trading Journal
- CRUD journal entry manual
- Auto-create journal dari import CSV
- Emotion score, analisis, pelajaran, strategi
- Link ke trade history
- Upload screenshot
- Journal review page

### 🏆 Leaderboard
- Peringkat trader (opt-in)
- Statistik publik per user

### 👤 Public Profile
- Profil publik trader (slug custom)
- Share statistik trading
- Opt-in leaderboard

### 🔗 Integrations
- **Jurnal Trading** — import data dari app Jurnal Trading (lama)
- **EA Logger API** — endpoint REST untuk EA MT4/MT5:
  - `POST /api/ea/trade` — kirim satu trade
  - `POST /api/ea/trade/batch` — kirim batch (max 50)
  - `POST /api/ea/heartbeat` — sinyal EA aktif
  - `GET /api/ea/config` — minta konfigurasi

### 🔔 Notifications
- Push notifications (Web Push API)
- Notification settings per user
- Subscribe/unsubscribe

### 🛡️ Admin Panel
- Manage users (role, view detail, delete)
- Admin dashboard

### 🎨 UI/UX
- Dark theme default
- Toggle light/dark theme
- Responsive (mobile-friendly)
- Toast notifications
- Loading states

---

## 🛠️ Tech Stack

| Layer | Tech |
|-------|------|
| **Backend** | Laravel 13, PHP 8.3 |
| **Database** | MySQL 8.0 |
| **Frontend** | Blade, Tailwind CSS 3.4, Alpine.js |
| **Charts** | Chart.js 4.4 |
| **Icons** | Font Awesome 6.5 |
| **Excel** | Maatwebsite Excel 3.1 |
| **Build** | Vite |
| **Auth** | Laravel Breeze |

---

## 📦 Installation

```bash
git clone https://github.com/andrizpray/journal-trading-connect.git
cd journal-trading-connect
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `.env`:
```env
DB_DATABASE=journal_trading_connect
DB_USERNAME=root
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.sumopod.com
MAIL_PORT=465
MAIL_USERNAME=noreply@eatrade-journal.site
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@eatrade-journal.site
```

```bash
php artisan migrate
npm run build
php artisan serve
```

---

## 📁 Project Structure

```
journal-trading-connect/
├── app/
│   ├── Exports/           # Excel exports (TradeHistoryExport)
│   ├── Helpers/           # Helper functions
│   ├── Http/
│   │   ├── Controllers/   # 14 controllers
│   │   └── Middleware/    # EaTokenAuth, IsAdmin
│   ├── Models/            # User, TradingAccount, TradeHistory, JournalEntry, etc.
│   ├── Notifications/     # Email verification (custom dark template)
│   └── Providers/
├── database/migrations/   # 15 migrations
├── resources/views/       # Blade templates (dark theme)
├── routes/
│   ├── web.php            # 30+ web routes
│   ├── api.php            # EA Logger API (4 endpoints)
│   └── auth.php           # Breeze auth routes
└── docs/
    └── ROADMAP.md         # Development roadmap
```

---

## 📋 Models

- **User** — auth, roles (user/admin), theme, public profile, notification settings, leaderboard opt-in
- **TradingAccount** — multi akun MT4/MT5, currency, API token
- **TradeHistory** — riwayat trade lengkap (open/close, pair, lot, P&L, duration)
- **JournalEntry** — journal trading (emotion, analisis, pelajaran, strategi, screenshot)
- **ImportLog** — log import CSV
- **PushSubscription** — web push subscriptions

---

## 👥 Author

- **Andriz** — [@andrizpray](https://github.com/andrizpray)

---

## 📄 License

This project is open-sourced under the [MIT license](LICENSE).
