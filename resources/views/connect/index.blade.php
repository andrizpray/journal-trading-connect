@extends('layouts.app')
@section('page-title', 'Connect')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-white">
        <i class="fas fa-link mr-2 text-cyan-400"></i>Connect & Import
    </h1>
    <p class="text-sm mt-1" style="color: var(--text-secondary);">Hubungkan sumber data trading Anda</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- 4.3 — CSV Import (existing) --}}
    <div class="card p-5 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                <i class="fas fa-file-csv text-white text-sm"></i>
            </div>
            <div>
                <h3 class="font-semibold text-white">Import CSV</h3>
                <p class="text-[10px]" style="color: var(--text-secondary);">MT4, MT5, TradingView, cTrader</p>
            </div>
        </div>
        <p class="text-xs mb-4" style="color: var(--text-secondary);">
            Upload file CSV riwayat trade. Auto-detect format dari header kolom.
        </p>
        <a href="{{ route('import.index') }}" class="btn-primary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
            <i class="fas fa-file-import"></i>Import CSV
        </a>
    </div>

    {{-- 4.4 — Copy from Jurnal Trading --}}
    <div class="card p-5 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #a855f7, #7c3aed);">
                <i class="fas fa-exchange-alt text-white text-sm"></i>
            </div>
            <div>
                <h3 class="font-semibold text-white">Jurnal Trading</h3>
                <p class="text-[10px]" style="color: var(--text-secondary);">Copy journal dari project lama</p>
            </div>
        </div>

        @if($journalConnected)
            <div class="flex items-center gap-2 mb-3 p-2 rounded-lg" style="background-color: var(--bg-secondary);">
                <i class="fas fa-check-circle text-emerald-400 text-xs"></i>
                <span class="text-xs text-emerald-400 font-medium">Terhubung</span>
                <span class="text-[10px]" style="color: var(--text-secondary);">— {{ number_format($journalCount, 0, ',', '.') }} journal ditemukan</span>
            </div>

            @if($accounts->isNotEmpty())
                <form action="{{ route('connect.import-jurnal') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Import ke Akun</label>
                        <select name="trading_account_id" required class="dark-input w-full text-sm">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->broker }} - {{ $acc->account_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-[10px]" style="color: var(--text-secondary);">Journal akan diimport sebagai jurnal manual (bukan trade history). Duplikat otomatis dilewati.</p>
                    <button type="submit" class="btn-primary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
                        <i class="fas fa-download"></i>Import dari Jurnal Trading
                    </button>
                </form>
            @else
                <p class="text-xs mb-3" style="color: var(--text-secondary);">Tambahkan akun trading terlebih dahulu.</p>
                <a href="{{ route('trading-accounts.index') }}" class="btn-secondary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
                    <i class="fas fa-plus"></i>Tambah Akun
                </a>
            @endif
        @else
            <div class="flex items-center gap-2 mb-3 p-2 rounded-lg" style="background-color: var(--bg-secondary);">
                <i class="fas fa-times-circle text-red-400 text-xs"></i>
                <span class="text-xs text-red-400 font-medium">Tidak terhubung</span>
            </div>
            <p class="text-xs" style="color: var(--text-secondary);">
                Database Jurnal Trading tidak ditemukan. Pastikan project jurnal-trading berada di server yang sama dan database <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">trading_journal</code> tersedia.
            </p>
        @endif
    </div>

    {{-- EA Logger --}}
    <div class="card p-5 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-robot text-white text-sm"></i>
            </div>
            <div>
                <h3 class="font-semibold text-white">EA Logger</h3>
                <p class="text-[10px]" style="color: var(--text-secondary);">Auto-sync dari MT4/MT5</p>
            </div>
        </div>
        <p class="text-xs mb-3" style="color: var(--text-secondary);">
            Install EA Logger di terminal MT4/MT5. Trade otomatis tersinkronisasi setiap 30 detik. 100% gratis.
        </p>
        <a href="{{ route('connect.ea-logger') }}" class="btn-primary flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg text-sm font-medium">
            <i class="fas fa-cog"></i>Setup EA Logger
        </a>
    </div>

    {{-- 4.5 — Telegram Notif (Coming Soon) --}}
    <div class="card p-5 sm:p-6 opacity-70">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                <i class="fab fa-telegram text-white text-sm"></i>
            </div>
            <div>
                <h3 class="font-semibold text-white">Telegram Notif <span class="text-[10px] px-2 py-0.5 rounded-full bg-yellow-900/30 text-yellow-400 ml-1">Soon</span></h3>
                <p class="text-[10px]" style="color: var(--text-secondary);">Notifikasi trade via Telegram</p>
            </div>
        </div>
        <p class="text-xs mb-3" style="color: var(--text-secondary);">
            Terima notifikasi saat import selesai dan ringkasan harian langsung di Telegram.
        </p>
        <div class="text-xs p-3 rounded-lg" style="background-color: var(--bg-secondary); color: var(--text-secondary);">
            <i class="fas fa-info-circle mr-1 text-yellow-400"></i>
            Memerlukan Telegram Bot token. Hubungi developer untuk setup.
        </div>
    </div>
</div>
@endsection
