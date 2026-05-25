@extends('layouts.app')

@section('title', 'Pengaturan Public Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
            <i class="fas fa-share-nodes text-cyan-400"></i> Public Profile
        </h1>
        <p class="text-gray-400 text-xs sm:text-sm mt-1">Bagikan stats trading Anda secara publik</p>
    </div>

    {{-- Toggle --}}
    <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-white">Aktifkan Public Profile</div>
                <div class="text-xs text-gray-400 mt-0.5">Orang lain bisa melihat stats Anda via link</div>
            </div>
            <form method="POST" action="{{ route('public-profile.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="public_profile_enabled" value="1">
                @if($user->public_profile_enabled)
                    <button type="button" onclick="confirmDisable()"
                        class="px-4 py-2 rounded-lg text-sm bg-red-900/50 border border-red-700/50 text-red-400 hover:bg-red-900/70 transition-colors">
                        <i class="fas fa-eye-slash mr-1"></i> Nonaktifkan
                    </button>
                @else
                    <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-eye mr-1"></i> Aktifkan
                    </button>
                @endif
            </form>
        </div>

        {{-- Public link --}}
        @if($user->public_profile_enabled && $user->public_slug)
            <div class="mt-4 p-3 rounded-lg bg-gray-900/50 border border-gray-700/30">
                <div class="text-xs text-gray-400 mb-1">Link publik Anda:</div>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-sm text-cyan-400 truncate bg-gray-800 px-3 py-1.5 rounded" id="publicUrl">{{ $user->getPublicUrl() }}</code>
                    <button onclick="copyLink()" class="text-gray-400 hover:text-white px-2 py-1.5 transition-colors" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                    <form method="POST" action="{{ route('public-profile.regenerate-slug') }}" class="flex-shrink-0">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-amber-400 px-2 py-1.5 transition-colors text-xs" title="Generate link baru">
                            <i class="fas fa-rotate"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    {{-- Visible Fields (only show if enabled) --}}
    @if($user->public_profile_enabled)
        <form method="POST" action="{{ route('public-profile.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="public_profile_enabled" value="1">

            <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4 sm:p-5">
                <h2 class="text-sm font-semibold text-white mb-1">Data yang Ditampilkan</h2>
                <p class="text-xs text-gray-400 mb-4">Pilih informasi apa saja yang ingin ditampilkan di public profile Anda.</p>

                <div class="space-y-3">
                    @foreach([
                        'total_trades' => ['Total Trades', 'Jumlah semua trade yang dieksekusi', 'fa-list-ol'],
                        'win_rate' => ['Win Rate', 'Persentase trade yang profit', 'fa-chart-pie'],
                        'total_pnl' => ['Total P&L', 'Total profit/loss dari semua trade', 'fa-dollar-sign'],
                        'equity_curve' => ['Equity Curve', 'Grafik pertumbuhan equity sepanjang waktu', 'fa-chart-line'],
                        'pair_performance' => ['Performa per Pair', 'P&L untuk setiap currency pair', 'fa-table'],
                        'risk_metrics' => ['Risk Metrics', 'Profit factor, max drawdown, avg win/loss', 'fa-shield-halved'],
                        'recent_trades' => ['20 Trade Terakhir', 'Detail 20 trade terakhir', 'fa-history'],
                    ] as $field => $info)
                        <label class="flex items-center gap-3 p-3 rounded-lg bg-gray-900/50 hover:bg-gray-700/30 cursor-pointer transition-colors">
                            <input type="checkbox" name="public_visible_fields[]" value="{{ $field }}"
                                {{ in_array($field, $user->public_visible_fields ?? []) ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0">
                            <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center flex-shrink-0">
                                <i class="fas {{ $info[2] }} text-cyan-400 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-white">{{ $info[0] }}</div>
                                <div class="text-xs text-gray-400">{{ $info[1] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="btn-primary w-full mt-4 px-4 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    @endif
</div>

{{-- Disable Confirmation Modal --}}
<div id="disableModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 max-w-sm mx-4 w-full">
        <h3 class="text-lg font-bold text-white mb-2">Nonaktifkan Public Profile?</h3>
        <p class="text-sm text-gray-400 mb-5">Link publik Anda tidak akan bisa diakses lagi. Data yang sudah dibagikan sebelumnya akan hilang.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="document.getElementById('disableModal').classList.add('hidden'); document.getElementById('disableModal').classList.remove('flex')"
                class="px-4 py-2 rounded-lg text-sm text-gray-300 hover:bg-gray-700 transition-colors">
                Batal
            </button>
            <form method="POST" action="{{ route('public-profile.update') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-red-600 hover:bg-red-700 text-white transition-colors">
                    Nonaktifkan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDisable() {
    document.getElementById('disableModal').classList.remove('hidden');
    document.getElementById('disableModal').classList.add('flex');
}

function copyLink() {
    const url = document.getElementById('publicUrl').textContent;
    navigator.clipboard.writeText(url).then(function() {
        showToast('Link berhasil disalin!', 'success');
    });
}
</script>
@endsection
