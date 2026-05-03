@extends('layouts.app')
@section('page-title', 'EA Logger Setup')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('connect.index') }}" class="text-cyan-400 hover:text-cyan-300 text-sm">
            <i class="fas fa-arrow-left mr-1"></i>Connect
        </a>
    </div>
    <h1 class="text-xl sm:text-2xl font-bold text-white">
        <i class="fas fa-robot mr-2 text-emerald-400"></i>EA Logger Setup
    </h1>
    <p class="text-sm mt-1" style="color: var(--text-secondary);">Setup EA Logger untuk auto-sync trade dari MT4/MT5</p>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-emerald-900/30 border border-emerald-700/30 text-emerald-400 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        @if(session('new_token'))
            <div class="mt-2 p-2 rounded bg-gray-900/50 text-xs font-mono break-all select-all">
                {{ session('new_token') }}
            </div>
        @endif
    </div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-900/30 border border-red-700/30 text-red-400 text-sm">
        <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
    </div>
@endif

@if($accounts->isEmpty())
    <div class="card p-6 text-center">
        <i class="fas fa-exclamation-triangle text-3xl text-yellow-400 mb-3"></i>
        <p class="text-white font-medium mb-2">Belum ada akun trading</p>
        <p class="text-sm mb-4" style="color: var(--text-secondary);">Tambahkan akun trading terlebih dahulu sebelum setup EA Logger.</p>
        <a href="{{ route('trading-accounts.index') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-plus"></i>Tambah Akun
        </a>
    </div>
@else

    {{-- Step 1: Pilih Akun --}}
    <div class="card p-5 sm:p-6 mb-4">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs flex items-center justify-center font-bold">1</span>
            <h3 class="font-semibold text-white">Pilih Akun Trading</h3>
        </div>
        <form method="GET" action="{{ route('connect.ea-logger') }}" class="mb-4">
            <select name="account_id" class="dark-input w-full text-sm" onchange="this.form.submit()">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ ($selectedId == $acc->id) ? 'selected' : '' }}>
                        {{ $acc->broker }} - {{ $acc->account_number }} ({{ strtoupper($acc->platform ?? '?') }})
                        @if($acc->last_synced_at)
                            — Sync: {{ $acc->last_synced_at->diffForHumans() }}
                        @endif
                    </option>
                @endforeach
            </select>
        </form>

        @if($selectedAccount)
            {{-- Akun info --}}
            <div class="p-3 rounded-lg text-xs" style="background-color: var(--bg-secondary);">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <div>
                        <span style="color: var(--text-secondary);">Broker:</span>
                        <span class="text-white ml-1">{{ $selectedAccount->broker }}</span>
                    </div>
                    <div>
                        <span style="color: var(--text-secondary);">Akun:</span>
                        <span class="text-white ml-1">{{ $selectedAccount->account_number }}</span>
                    </div>
                    <div>
                        <span style="color: var(--text-secondary);">Platform:</span>
                        <span class="text-white ml-1 uppercase">{{ $selectedAccount->platform ?? '-' }}</span>
                    </div>
                    @if($selectedAccount->last_synced_at)
                    <div>
                        <span style="color: var(--text-secondary);">Last Sync:</span>
                        <span class="text-emerald-400 ml-1">{{ $selectedAccount->last_synced_at->diffForHumans() }}</span>
                    </div>
                    @endif
                    <div>
                        <span style="color: var(--text-secondary);">Total Trades:</span>
                        <span class="text-white ml-1">{{ $selectedAccount->total_trades ?? 0 }}</span>
                    </div>
                    <div>
                        <span style="color: var(--text-secondary);">Total P&L:</span>
                        <span class="ml-1 {{ ($selectedAccount->total_pnl ?? 0) >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ currency_symbol($selectedAccount->currency ?? 'USD') }}{{ number_format($selectedAccount->total_pnl ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($selectedAccount)

    {{-- Step 2: API Token --}}
    <div class="card p-5 sm:p-6 mb-4">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs flex items-center justify-center font-bold">2</span>
            <h3 class="font-semibold text-white">API Token</h3>
        </div>
        <p class="text-xs mb-3" style="color: var(--text-secondary);">
            Token ini digunakan oleh EA Logger untuk autentikasi. Jangan bagikan token ke orang lain.
        </p>
        <div class="flex items-center gap-2">
            <div class="flex-1 p-2 rounded-lg bg-gray-900 font-mono text-xs break-all select-all text-cyan-400" id="tokenDisplay">
                {{ $selectedAccount->api_token }}
            </div>
            <button onclick="copyToken()" class="px-3 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition" title="Copy token">
                <i class="fas fa-copy"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('connect.ea-logger.regenerate-token', $selectedAccount->id) }}" class="mt-3" onsubmit="return confirm('Token lama tidak akan bisa digunakan lagi. Lanjutkan?')">
            @csrf
            <button type="submit" class="text-xs text-yellow-400 hover:text-yellow-300 transition">
                <i class="fas fa-sync-alt mr-1"></i>Generate Token Baru
            </button>
        </form>
    </div>

    {{-- Step 3: Download EA --}}
    <div class="card p-5 sm:p-6 mb-4">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs flex items-center justify-center font-bold">3</span>
            <h3 class="font-semibold text-white">Download EA Logger</h3>
        </div>
        <p class="text-xs mb-3" style="color: var(--text-secondary);">
            Download file EA sesuai platform terminal Anda. File bisa langsung dipakai atau di-compile ulang.
        </p>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ asset('ea/JTC_Logger.mq4') }}" download class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium bg-blue-600/20 text-blue-400 border border-blue-700/30 hover:bg-blue-600/30 transition">
                <i class="fas fa-download"></i>MT4 (.mq4)
            </a>
            <a href="{{ asset('ea/JTC_Logger.mq5') }}" download class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-medium bg-purple-600/20 text-purple-400 border border-purple-700/30 hover:bg-purple-600/30 transition">
                <i class="fas fa-download"></i>MT5 (.mq5)
            </a>
        </div>
    </div>

    {{-- Step 4: Panduan Install --}}
    <div class="card p-5 sm:p-6 mb-4">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs flex items-center justify-center font-bold">4</span>
            <h3 class="font-semibold text-white">Panduan Install</h3>
        </div>

        <div class="space-y-3 text-xs">
            {{-- Step 4.1 --}}
            <div class="p-3 rounded-lg" style="background-color: var(--bg-secondary);">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-emerald-400 font-bold">4.1</span>
                    <span class="text-white font-medium">Copy file EA ke terminal</span>
                </div>
                <ol class="space-y-1 ml-5" style="color: var(--text-secondary);">
                    <li>1. Buka MT4/MT5 → <strong class="text-white">File → Open Data Folder</strong></li>
                    <li>2. Buka folder <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">MQL4/Indicators</code> (MT4) atau <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">MQL5/Indicators</code> (MT5)</li>
                    <li>3. Copy file yang didownload ke folder tersebut</li>
                </ol>
            </div>

            {{-- Step 4.2 --}}
            <div class="p-3 rounded-lg" style="background-color: var(--bg-secondary);">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-emerald-400 font-bold">4.2</span>
                    <span class="text-white font-medium">Compile EA</span>
                </div>
                <ol class="space-y-1 ml-5" style="color: var(--text-secondary);">
                    <li>1. Buka <strong class="text-white">MetaEditor</strong> (tekan F4)</li>
                    <li>2. Buka file <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">JTC_Logger.mq4</code> atau <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">.mq5</code></li>
                    <li>3. Klik <strong class="text-white">Compile</strong> (tekan F7)</li>
                    <li>4. Pastikan muncul <span class="text-emerald-400">"0 errors, 0 warnings"</span></li>
                </ol>
            </div>

            {{-- Step 4.3 --}}
            <div class="p-3 rounded-lg" style="background-color: var(--bg-secondary);">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-emerald-400 font-bold">4.3</span>
                    <span class="text-white font-medium">Attach ke chart & isi token</span>
                </div>
                <ol class="space-y-1 ml-5" style="color: var(--text-secondary);">
                    <li>1. Buka <strong class="text-white">Navigator</strong> (tekan Ctrl+N)</li>
                    <li>2. Cari <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">Indicators → JTC_Logger</code></li>
                    <li>3. <strong class="text-white">Drag</strong> ke chart manapun</li>
                    <li>4. Di parameter, paste <strong class="text-white">API Token</strong> dari langkah 2</li>
                </ol>
            </div>

            {{-- Step 4.4 --}}
            <div class="p-3 rounded-lg border border-yellow-700/30 bg-yellow-900/10">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-yellow-400 font-bold">4.4 ⚠️</span>
                    <span class="text-white font-medium">Allow WebRequest (PENTING!)</span>
                </div>
                <ol class="space-y-1 ml-5" style="color: var(--text-secondary);">
                    <li>1. Klik <strong class="text-white">Tools → Options</strong></li>
                    <li>2. Tab <strong class="text-white">Expert Advisors</strong></li>
                    <li>3. Centang <strong class="text-white">"Allow WebRequest for listed URL"</strong></li>
                    <li>4. Klik tombol <strong class="text-white">+</strong> lalu tambahkan: <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">http://43.134.37.14:8081</code></li>
                    <li>5. Klik <strong class="text-white">OK</strong></li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Step 5: Test Koneksi --}}
    <div class="card p-5 sm:p-6 mb-4">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs flex items-center justify-center font-bold">5</span>
            <h3 class="font-semibold text-white">Test Koneksi</h3>
        </div>
        <p class="text-xs mb-3" style="color: var(--text-secondary);">
            Kirim heartbeat test untuk memastikan EA Logger bisa terhubung ke server.
        </p>
        <form method="POST" action="{{ route('connect.ea-logger.test') }}">
            @csrf
            <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
            <button type="submit" class="btn-primary flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-satellite-dish"></i>Test Koneksi
            </button>
        </form>
    </div>

    @endif

@endif

@endsection

@section('scripts')
<script>
function copyToken() {
    const tokenEl = document.getElementById('tokenDisplay');
    const text = tokenEl.textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('bg-cyan-600');
        btn.classList.add('bg-emerald-600');
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.classList.remove('bg-emerald-600');
            btn.classList.add('bg-cyan-600');
        }, 2000);
    });
}
</script>
@endsection
