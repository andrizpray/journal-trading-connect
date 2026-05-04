@extends('layouts.app')
@section('page-title', 'EA Logger Setup')

@section('content')
@php
    $freshToken = (session('regenerated_account_id') == ($selectedAccount->id ?? null))
        ? session('new_token')
        : null;
@endphp
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
            {{-- Akun info dengan connection status --}}
            <div class="p-3 rounded-lg text-xs" style="background-color: var(--bg-secondary);">
                {{-- Connection Status Banner --}}
                <div class="flex items-center gap-2 mb-3 pb-2 border-b" style="border-color: var(--border-color);">
                    @php
                        $isConnected = $selectedAccount->last_synced_at && $selectedAccount->last_synced_at->gt(now()->subMinutes(10));
                    @endphp
                    @if($isConnected)
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-emerald-400 font-medium">EA Terhubung</span>
                        </span>
                        <span class="text-[10px]" style="color: var(--text-secondary);">
                            Terakhir aktif {{ $selectedAccount->last_synced_at->diffForHumans() }}
                        </span>
                    @elseif($selectedAccount->last_synced_at)
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                            <span class="text-yellow-400 font-medium">EA Tidak Aktif</span>
                        </span>
                        <span class="text-[10px]" style="color: var(--text-secondary);">
                            Terakhir sync {{ $selectedAccount->last_synced_at->diffForHumans() }}
                        </span>
                    @else
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                            <span class="text-gray-400 font-medium">Belum Pernah Terhubung</span>
                        </span>
                    @endif
                </div>
                
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
            @if($freshToken)
                <div class="flex-1 p-2 rounded-lg bg-gray-900 font-mono text-xs break-all select-all text-cyan-400" id="tokenDisplay" data-token="{{ $freshToken }}">{{ $freshToken }}</div>
                <button onclick="copyToken(event)" class="px-3 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition" title="Copy token">
                    <i class="fas fa-copy"></i>
                </button>
            @else
                <div class="flex-1 p-2 rounded-lg bg-gray-900/70 text-xs text-gray-400 border border-gray-700/50">
                    Token disembunyikan untuk keamanan. Generate token baru untuk melihat dan menyalin token.
                </div>
            @endif
        </div>
        @if($freshToken)
            <p class="text-[11px] mt-2 text-yellow-300">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                Token ini hanya ditampilkan sekali. Simpan segera ke parameter EA.
            </p>
        @endif
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
                    <li>4. Klik tombol <strong class="text-white">+</strong> lalu tambahkan: <code class="px-1 py-0.5 rounded bg-gray-800 text-cyan-400">https://eatrade-journal.site</code></li>
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
        
        {{-- Test Result Area --}}
        <div id="testResult" class="hidden mb-4">
            {{-- Will be populated by JS --}}
        </div>

        <button type="button" id="testConnectionBtn" onclick="testConnection({{ $selectedAccount->id }})" 
            class="btn-primary flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-satellite-dish"></i>
            <span id="testBtnText">Test Koneksi</span>
        </button>
    </div>

    @endif

@endif

@endsection

@push('scripts')
<script>
function copyToken(event) {
    const tokenEl = document.getElementById('tokenDisplay');
    const text = tokenEl.getAttribute('data-token');
    const btn = event.currentTarget || event.target.closest('button');

    const setCopiedState = () => {
        if (!btn) return;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('bg-cyan-600');
        btn.classList.add('bg-emerald-600');
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.classList.remove('bg-emerald-600');
            btn.classList.add('bg-cyan-600');
        }, 2000);
    };

    if (!text) {
        showToast('Token kosong atau tidak ditemukan.', 'error');
        return;
    }

    const fallbackCopy = () => {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.setAttribute('readonly', '');
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (_) {
            copied = false;
        }

        document.body.removeChild(textArea);

        if (copied) {
            setCopiedState();
            showToast('Token berhasil disalin.', 'success');
        } else {
            showToast('Gagal menyalin otomatis. Silakan copy manual.', 'error');
        }
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text)
            .then(() => {
                setCopiedState();
                showToast('Token berhasil disalin.', 'success');
            })
            .catch(() => fallbackCopy());
        return;
    }

    fallbackCopy();
}

function testConnection(accountId) {
    const btn = document.getElementById('testConnectionBtn');
    const btnText = document.getElementById('testBtnText');
    const resultDiv = document.getElementById('testResult');
    
    btn.disabled = true;
    btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Mengecek...';
    resultDiv.classList.add('hidden');
    
    fetch('{{ route("connect.ea-logger.test-ajax") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ account_id: accountId })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btnText.innerHTML = 'Test Koneksi';
        resultDiv.classList.remove('hidden');

        const s = data.status;
        
        if (s === 'connected') {
            // EA aktif dan terhubung
            resultDiv.innerHTML = `
                <div class="p-4 rounded-lg bg-emerald-900/30 border border-emerald-600/50">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-emerald-400 font-semibold">${data.title}</span>
                    </div>
                    <p class="text-xs text-emerald-300/80 mb-3">${data.message}</p>
                    <div class="text-xs space-y-1.5 p-3 rounded-lg bg-emerald-900/20">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-tag text-emerald-400/60 w-4"></i>
                            <span class="text-emerald-300/60">Akun:</span>
                            <span class="text-white">${data.details.account}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock text-emerald-400/60 w-4"></i>
                            <span class="text-emerald-300/60">Terakhir sync:</span>
                            <span class="text-white">${data.details.last_sync}</span>
                            <span class="text-emerald-400">(${data.details.last_sync_ago})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-chart-line text-emerald-400/60 w-4"></i>
                            <span class="text-emerald-300/60">Total trade tersinkron:</span>
                            <span class="text-white font-semibold">${data.details.total_trades.toLocaleString()}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-server text-emerald-400/60 w-4"></i>
                            <span class="text-emerald-300/60">Server:</span>
                            <code class="bg-gray-800 px-1.5 py-0.5 rounded text-cyan-400">${data.details.server_url}</code>
                        </div>
                    </div>
                </div>
            `;
            showToast(data.title, 'success');
        }
        else if (s === 'disconnected') {
            // Server OK tapi EA tidak aktif
            resultDiv.innerHTML = `
                <div class="p-4 rounded-lg bg-yellow-900/30 border border-yellow-600/50">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                        <span class="text-yellow-400 font-semibold">${data.title}</span>
                    </div>
                    <p class="text-xs text-yellow-300/80 mb-3">${data.message}</p>
                    <div class="text-xs space-y-1.5 p-3 rounded-lg bg-yellow-900/20 mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-user-tag text-yellow-400/60 w-4"></i>
                            <span class="text-yellow-300/60">Akun:</span>
                            <span class="text-white">${data.details.account}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock text-yellow-400/60 w-4"></i>
                            <span class="text-yellow-300/60">Terakhir sync:</span>
                            <span class="text-white">${data.details.last_sync}</span>
                            <span class="text-yellow-400">(${data.details.last_sync_ago})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-chart-line text-yellow-400/60 w-4"></i>
                            <span class="text-yellow-300/60">Total trade:</span>
                            <span class="text-white">${data.details.total_trades.toLocaleString()}</span>
                        </div>
                    </div>
                    ${data.hint ? `
                        <div class="text-xs p-3 rounded-lg bg-gray-900/50 border border-yellow-700/30">
                            <i class="fas fa-lightbulb text-yellow-400 mr-1"></i>
                            <span class="text-yellow-300">${data.hint}</span>
                        </div>
                    ` : ''}
                </div>
            `;
            showToast(data.title, 'error');
        }
        else if (s === 'not_setup') {
            // Server OK tapi EA belum pernah terhubung
            resultDiv.innerHTML = `
                <div class="p-4 rounded-lg bg-cyan-900/30 border border-cyan-600/50">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-cyan-400"></span>
                        <span class="text-cyan-400 font-semibold">${data.title}</span>
                    </div>
                    <p class="text-xs text-cyan-300/80 mb-3">${data.message}</p>
                    <div class="text-xs p-3 rounded-lg bg-cyan-900/20">
                        <p class="text-cyan-300 font-medium mb-2"><i class="fas fa-clipboard-list mr-1"></i>Langkah setup EA Logger:</p>
                        <ol class="space-y-1.5 ml-4">
                            ${data.steps.map((step, i) => `
                                <li class="flex items-start gap-2">
                                    <span class="w-5 h-5 rounded-full bg-cyan-600/30 text-cyan-400 text-[10px] flex items-center justify-center shrink-0 mt-0.5">${i+1}</span>
                                    <span class="text-cyan-200">${step}</span>
                                </li>
                            `).join('')}
                        </ol>
                    </div>
                </div>
            `;
            showToast(data.title, 'info');
        }
        else {
            // Server error
            resultDiv.innerHTML = `
                <div class="p-4 rounded-lg bg-red-900/30 border border-red-600/50">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-3 h-3 rounded-full bg-red-400"></span>
                        <span class="text-red-400 font-semibold">${data.title}</span>
                    </div>
                    <p class="text-xs text-red-300/80 mb-2">${data.message}</p>
                    ${data.hint ? `
                        <div class="text-xs p-3 rounded-lg bg-gray-900/50 border border-red-700/30">
                            <i class="fas fa-lightbulb text-yellow-400 mr-1"></i>
                            <span class="text-red-300">${data.hint}</span>
                        </div>
                    ` : ''}
                </div>
            `;
            showToast(data.title, 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btnText.innerHTML = 'Test Koneksi';
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = `
            <div class="p-4 rounded-lg bg-red-900/30 border border-red-600/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                    <span class="text-red-400">Terjadi kesalahan. Coba refresh halaman dan ulangi.</span>
                </div>
            </div>
        `;
        console.error('Test connection error:', err);
    });
}
</script>
@endpush
