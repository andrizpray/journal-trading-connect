@extends('layouts.app')
@section('page-title', 'Import CSV')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-white">
        <i class="fas fa-file-import mr-2 text-cyan-400"></i>Import CSV
    </h1>
    <p class="text-sm mt-1" style="color: var(--text-secondary);">
        Import riwayat trade dari file CSV MT4/MT5
        <span class="mx-1">·</span>
        <a href="{{ route('import.logs') }}" class="text-cyan-400 hover:text-cyan-300">Riwayat Import →</a>
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    {{-- Import Form --}}
    <div class="card p-5 sm:p-6 lg:col-span-2">
        @if($accounts->isEmpty())
            <div class="text-center py-10">
                <div class="w-16 h-16 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background-color: var(--bg-secondary);">
                    <i class="fas fa-wallet text-2xl" style="color: var(--text-secondary);"></i>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">Tambahkan akun trading terlebih dahulu sebelum import CSV.</p>
                <a href="{{ route('trading-accounts.index') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm mt-3">
                    <i class="fas fa-plus"></i>Tambah Akun
                </a>
            </div>
        @else
            <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data" x-data="{ dragging: false }"
                  @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false">
                @csrf

                {{-- Account Selector --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">
                        <i class="fas fa-wallet mr-1"></i>Pilih Akun Trading *
                    </label>
                    <select name="trading_account_id" required class="dark-input w-full">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->broker }} - {{ $account->account_number }} ({{ strtoupper($account->platform) }}){{ $account->account_name ? ' - ' . $account->account_name : '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- File Upload Zone --}}
                <div class="mb-5" :class="{ 'active': dragging }">
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">
                        <i class="fas fa-file-csv mr-1"></i>File CSV *
                    </label>
                    <div class="drop-zone rounded-xl p-8 text-center cursor-pointer relative"
                         :class="{ 'active': dragging }"
                         onclick="this.querySelector('input[type=file]').click()">
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               onchange="this.parentElement.querySelector('.file-name').textContent = this.files[0]?.name || 'Pilih file atau drag & drop di sini'">
                        <div class="pointer-events-none">
                            <i class="fas fa-cloud-upload-alt text-3xl mb-3" style="color: var(--accent-cyan);"></i>
                            <p class="file-name text-sm font-medium text-white">Pilih file atau drag & drop di sini</p>
                            <p class="text-xs mt-1" style="color: var(--text-secondary);">Format: .csv atau .txt (maks. 10MB)</p>
                        </div>
                    </div>
                </div>

                {{-- Options --}}
                <div class="mb-5">
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg" style="background-color: var(--bg-secondary);">
                        <input type="checkbox" name="auto_journal" class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-cyan-500 focus:ring-cyan-500">
                        <div>
                            <span class="text-sm font-medium text-white">Otomatis buat Jurnal</span>
                            <p class="text-xs" style="color: var(--text-secondary);">Buat jurnal otomatis dari setiap trade yang diimport</p>
                        </div>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" id="importBtn" onclick="handleImportSubmit(this)" class="btn-primary w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold">
                    <i class="fas fa-upload" id="importIcon"></i><span id="importText">Import Sekarang</span>
                </button>
            </form>
        @endif
    </div>

    {{-- Info Panel --}}
    <div class="space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-white mb-3">
                <i class="fas fa-info-circle mr-2 text-cyan-400"></i>Panduan Import
            </h3>
            <div class="space-y-3 text-xs" style="color: var(--text-secondary);">
                <div class="flex gap-2">
                    <span class="text-cyan-400 font-bold">1.</span>
                    <span>Tambahkan akun trading di menu <strong class="text-white">Akun Trading</strong></span>
                </div>
                <div class="flex gap-2">
                    <span class="text-cyan-400 font-bold">2.</span>
                    <span>Buka MT5 → <strong class="text-white">History</strong> tab → klik kanan → <strong class="text-white">Save as Report</strong></span>
                </div>
                <div class="flex gap-2">
                    <span class="text-cyan-400 font-bold">3.</span>
                    <span>Pilih format <strong class="text-white">CSV</strong></span>
                </div>
                <div class="flex gap-2">
                    <span class="text-cyan-400 font-bold">4.</span>
                    <span>Upload file dan pilih akun tujuan</span>
                </div>
                <div class="flex gap-2">
                    <span class="text-cyan-400 font-bold">5.</span>
                    <span>Klik <strong class="text-white">Import Sekarang</strong></span>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-white mb-3">
                <i class="fas fa-columns mr-2 text-purple-400"></i>Format Didukung
            </h3>
            <p class="text-xs mb-2" style="color: var(--text-secondary);">Auto-detect dari header kolom. Wajib: <span class="text-white font-medium">Symbol + Profit</span></p>
            <div class="space-y-2 mb-3">
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-cyan-900/30 text-cyan-400">MT4/MT5</span>
                    <span style="color: var(--text-secondary);">Ticket, Open Date, Close Date, Type, Lot, Symbol, ...</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-purple-900/30 text-purple-400">TradingView</span>
                    <span style="color: var(--text-secondary);">Type, Symbol, Qty, Entry Price, Close Price, P&L</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-900/30 text-emerald-400">cTrader</span>
                    <span style="color: var(--text-secondary);">Trade ID, Symbol, Side, Quantity, Open/Close Time, ...</span>
                </div>
            </div>
            <a href="{{ route('import.template') }}" class="btn-secondary flex items-center justify-center gap-2 w-full px-3 py-2 rounded-lg text-xs font-medium mt-1">
                <i class="fas fa-download"></i>Download Template CSV
            </a>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-white mb-3">
                <i class="fas fa-shield-alt mr-2 text-emerald-400"></i>Fitur
            </h3>
            <ul class="space-y-2 text-xs" style="color: var(--text-secondary);">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-emerald-400 mt-0.5"></i>
                    <span>Auto-detect format CSV (MT4 & MT5)</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-emerald-400 mt-0.5"></i>
                    <span>Skip trade yang sudah diimport (no duplicate)</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-emerald-400 mt-0.5"></i>
                    <span>Auto kalkulasi result (win/loss/break even)</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-emerald-400 mt-0.5"></i>
                    <span>Auto hitung durasi trade</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-emerald-400 mt-0.5"></i>
                    <span>Otomatis update statistik akun</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
function handleImportSubmit(btn) {
    var icon = document.getElementById('importIcon');
    var text = document.getElementById('importText');
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-not-allowed');
    icon.className = 'fas fa-spinner fa-spin';
    text.textContent = 'Mengimport...';
}
</script>
@endsection
