@extends('layouts.app')
@section('page-title', 'Akun Trading')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-wallet mr-2 text-cyan-400"></i>Akun Trading
        </h1>
        <p class="text-sm mt-1" style="color: var(--text-secondary);">Kelola akun MT4/MT5 yang terhubung</p>
    </div>
    <button onclick="document.getElementById('addAccountModal').classList.remove('hidden')" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium shrink-0">
        <i class="fas fa-plus"></i>Tambah Akun
    </button>
</div>

{{-- Add Account Modal --}}
<div id="addAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="card p-6 relative z-10 w-full max-w-md">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-plus-circle mr-2 text-cyan-400"></i>Tambah Akun Trading
        </h3>
        <form action="{{ route('trading-accounts.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Nomor Akun *</label>
                    <input type="text" name="account_number" required placeholder="Contoh: 12345678" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Nama Akun</label>
                    <input type="text" name="account_name" placeholder="Contoh: Akun Utama" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Broker *</label>
                    <input type="text" name="broker" required placeholder="Contoh: XM, OctaFX, ICMarkets" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Platform *</label>
                    <select name="platform" required class="dark-input w-full">
                        <option value="mt5">MetaTrader 5 (MT5)</option>
                        <option value="mt4">MetaTrader 4 (MT4)</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Mata Uang *</label>
                    <select name="currency" required class="dark-input w-full">
                        <option value="USD">USD</option>
                        <option value="IDR">IDR</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                        <option value="Cent">Cent (USC)</option>
                    </select>
                </div>
                <div class="pt-2 border-t space-y-3" style="border-color: var(--border-color);">
                    <p class="text-[10px] font-medium" style="color: var(--text-secondary);">Desimal harga (sesuai broker / MT4–MT5)</p>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Pair FX (EURUSD, GBPUSD, …)</label>
                        <input type="number" name="decimal_places_fx" min="0" max="8" value="{{ old('decimal_places_fx', 5) }}" class="dark-input w-full" title="Biasanya 5 digit (0.00001)">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Pair JPY (USDJPY, …)</label>
                        <input type="number" name="decimal_places_jpy" min="0" max="8" value="{{ old('decimal_places_jpy', 3) }}" class="dark-input w-full" title="Biasanya 3 digit (0.001)">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Logam (XAUUSD, XAGUSD, …)</label>
                        <input type="number" name="decimal_places_metal" min="0" max="8" value="{{ old('decimal_places_metal', 2) }}" class="dark-input w-full" title="Broker 2 atau 3 digit di belakang koma untuk emas">
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('addAccountModal').classList.add('hidden')" class="btn-secondary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">
                    Batal
                </button>
                <button type="submit" class="btn-primary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Account Cards --}}
@if($accounts->isEmpty())
    <div class="card p-12 text-center">
        <div class="w-20 h-20 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background-color: var(--bg-secondary);">
            <i class="fas fa-wallet text-3xl" style="color: var(--text-secondary);"></i>
        </div>
        <h3 class="font-bold text-lg text-white mb-2">Belum Ada Akun</h3>
        <p class="text-sm mb-6 max-w-xs mx-auto" style="color: var(--text-secondary);">
            Tambahkan akun trading MT4/MT5 untuk mulai import riwayat trade.
        </p>
        <button onclick="document.getElementById('addAccountModal').classList.remove('hidden')" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium">
            <i class="fas fa-plus"></i>Tambah Akun Pertama
        </button>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($accounts as $account)
        <div class="card p-5 relative group">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-white text-sm">{{ $account->broker }}</h4>
                        <span class="text-xs font-mono" style="color: var(--text-secondary);">{{ $account->account_number }}</span>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $account->platform === 'mt5' ? 'bg-cyan-900/30 text-cyan-400' : 'bg-purple-900/30 text-purple-400' }}">
                    {{ $account->platform }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-700/50 text-gray-300">
                    {{ currency_symbol($account->currency) }}
                </span>
            </div>

            @if($account->account_name)
                <p class="text-xs mb-3 px-2 py-1 rounded inline-block" style="background-color: var(--bg-secondary); color: var(--text-secondary);">
                    {{ $account->account_name }}
                </p>
            @endif

            <p class="text-[10px] leading-relaxed mb-2" style="color: var(--text-secondary);">
                Desimal harga: FX {{ $account->decimal_places_fx }} · JPY {{ $account->decimal_places_jpy }} · Logam {{ $account->decimal_places_metal }}
            </p>
            @php
                $quoteEditData = [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'broker' => $account->broker,
                    'platform' => $account->platform,
                    'currency' => $account->currency,
                    'decimal_places_fx' => $account->decimal_places_fx,
                    'decimal_places_jpy' => $account->decimal_places_jpy,
                    'decimal_places_metal' => $account->decimal_places_metal,
                ];
            @endphp
            <button type="button"
                class="edit-quote-btn text-[10px] text-cyan-400 hover:text-cyan-300 font-medium mb-2"
                data-account="{{ e(json_encode($quoteEditData)) }}">
                <i class="fas fa-sliders-h mr-1"></i>Ubah desimal harga
            </button>

            <div class="grid grid-cols-2 gap-3 mt-4 pt-3 border-t" style="border-color: var(--border-color);">
                <div>
                    <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Total Trade</div>
                    <div class="text-sm font-bold text-white">{{ number_format($account->trade_histories_count ?? $account->total_trades, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase" style="color: var(--text-secondary);">Total P&L</div>
                    <div class="text-sm font-bold {{ $account->total_pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ format_signed_currency($account->total_pnl, $account->currency, 0) }}
                    </div>
                </div>
            </div>

            @if($account->last_synced_at)
                <div class="text-[10px] mt-3 pt-2 border-t" style="border-color: var(--border-color); color: var(--text-secondary);">
                    <i class="fas fa-sync-alt mr-1"></i>Terakhir sync: {{ $account->last_synced_at->diffForHumans() }}
                </div>
            @endif

            {{-- Delete button --}}
            <form action="{{ route('trading-accounts.destroy', $account->id) }}" method="POST" class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                @csrf @method('DELETE')
                <button type="submit" onclick="return confirm('Hapus akun {{ $account->account_number }}? Semua data trade terkait akan ikut terhapus.')" class="text-gray-500 hover:text-red-400 transition-colors p-1" title="Hapus">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </form>
        </div>
        @endforeach
    </div>
@endif

{{-- Edit quote decimals --}}
<div id="editAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60" onclick="document.getElementById('editAccountModal').classList.add('hidden')"></div>
    <div class="card p-6 relative z-10 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-sliders-h mr-2 text-cyan-400"></i>Desimal harga &amp; akun
        </h3>
        <form id="editAccountForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Nama Akun</label>
                    <input type="text" name="account_name" id="edit_account_name" class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Broker *</label>
                    <input type="text" name="broker" id="edit_broker" required class="dark-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Platform *</label>
                    <select name="platform" id="edit_platform" required class="dark-input w-full">
                        <option value="mt5">MetaTrader 5 (MT5)</option>
                        <option value="mt4">MetaTrader 4 (MT4)</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Mata Uang *</label>
                    <select name="currency" id="edit_currency" required class="dark-input w-full">
                        <option value="USD">USD</option>
                        <option value="IDR">IDR</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                        <option value="Cent">Cent (USC)</option>
                    </select>
                </div>
                <div class="pt-2 border-t space-y-3" style="border-color: var(--border-color);">
                    <p class="text-[10px] font-medium" style="color: var(--text-secondary);">Desimal harga (XAUUSD: 2 atau 3 sesuai broker)</p>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Pair FX</label>
                        <input type="number" name="decimal_places_fx" id="edit_decimal_places_fx" min="0" max="8" required class="dark-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Pair JPY</label>
                        <input type="number" name="decimal_places_jpy" id="edit_decimal_places_jpy" min="0" max="8" required class="dark-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Logam (XAUUSD, …)</label>
                        <input type="number" name="decimal_places_metal" id="edit_decimal_places_metal" min="0" max="8" required class="dark-input w-full">
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('editAccountModal').classList.add('hidden')" class="btn-secondary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">
                    Batal
                </button>
                <button type="submit" class="btn-primary flex-1 px-4 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.edit-quote-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var d = JSON.parse(this.getAttribute('data-account'));
        document.getElementById('editAccountForm').action = '{{ url('/trading-accounts') }}/' + d.id;
        document.getElementById('edit_account_name').value = d.account_name || '';
        document.getElementById('edit_broker').value = d.broker || '';
        document.getElementById('edit_platform').value = d.platform || 'mt5';
        document.getElementById('edit_currency').value = d.currency || 'USD';
        document.getElementById('edit_decimal_places_fx').value = d.decimal_places_fx;
        document.getElementById('edit_decimal_places_jpy').value = d.decimal_places_jpy;
        document.getElementById('edit_decimal_places_metal').value = d.decimal_places_metal;
        document.getElementById('editAccountModal').classList.remove('hidden');
    });
});
</script>
@endpush
@endsection
