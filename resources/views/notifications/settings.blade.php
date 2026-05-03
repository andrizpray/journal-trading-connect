@extends('layouts.app')
@section('page-title', 'Pengaturan Notifikasi')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('profile.edit') }}" class="text-gray-400 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-xl sm:text-2xl font-bold text-white">
            <i class="fas fa-bell text-cyan-400 mr-2"></i>Pengaturan Notifikasi
        </h1>
    </div>
    <p class="text-gray-400 text-sm">Atur reminder dan notifikasi push browser.</p>
</div>

@session('success')
<div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-4">
    <i class="fas fa-check-circle mr-2"></i>{{ $value }}
</div>
@endsession

{{-- HTTPS Warning --}}
@if(!request()->secure())
<div class="card p-4 mb-4 border-yellow-500/30" style="background: rgba(245,158,11,0.05);">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5"></i>
        <div>
            <h3 class="text-yellow-400 font-medium text-sm">HTTPS diperlukan</h3>
            <p class="text-gray-400 text-xs mt-1">
                Push notification hanya bisa aktif jika server menggunakan HTTPS. 
                Saat ini server belum pakai HTTPS. Fitur ini akan otomatis aktif setelah SSL di-setup.
            </p>
        </div>
    </div>
</div>
@endif

{{-- Notification Preferences --}}
<form method="POST" action="{{ route('notifications.update') }}">
    @csrf

    <div class="card p-4 sm:p-5 mb-4">
        <h2 class="text-white font-semibold mb-4">
            <i class="fas fa-clock mr-2 text-cyan-400"></i>Reminder
        </h2>

        {{-- Daily Journal --}}
        <div class="flex items-center justify-between py-3 border-b border-gray-700/50">
            <div>
                <div class="text-white text-sm font-medium">Reminder Journal Harian</div>
                <div class="text-gray-400 text-xs mt-0.5">Ingatkan untuk menulis journal setiap hari</div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="notify_daily_journal" value="1"
                    {{ $user->notify_daily_journal ? 'checked' : '' }}
                    class="sr-only peer">
                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500"></div>
            </label>
        </div>

        {{-- Daily Journal Time --}}
        <div id="daily-time-section" class="{{ $user->notify_daily_journal ? '' : 'hidden' }} py-3 border-b border-gray-700/50 pl-4">
            <label class="text-gray-400 text-xs block mb-1">Waktu Reminder</label>
            <input type="time" name="daily_journal_time" 
                value="{{ $user->daily_journal_time ?? '20:00' }}"
                class="dark-input w-32 text-sm">
        </div>

        {{-- Weekly Review --}}
        <div class="flex items-center justify-between py-3 border-b border-gray-700/50">
            <div>
                <div class="text-white text-sm font-medium">Reminder Review Mingguan</div>
                <div class="text-gray-400 text-xs mt-0.5">Review performa trading setiap minggu</div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="notify_weekly_review" value="1"
                    {{ $user->notify_weekly_review ? 'checked' : '' }}
                    class="sr-only peer">
                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500"></div>
            </label>
        </div>

        {{-- Weekly Review Day --}}
        <div id="weekly-day-section" class="{{ $user->notify_weekly_review ? '' : 'hidden' }} py-3 border-b border-gray-700/50 pl-4">
            <label class="text-gray-400 text-xs block mb-1">Hari Review</label>
            <select name="weekly_review_day" class="dark-input w-40 text-sm">
                <option value="monday" {{ ($user->weekly_review_day ?? 'sunday') === 'monday' ? 'selected' : '' }}>Senin</option>
                <option value="tuesday" {{ ($user->weekly_review_day ?? 'sunday') === 'tuesday' ? 'selected' : '' }}>Selasa</option>
                <option value="wednesday" {{ ($user->weekly_review_day ?? 'sunday') === 'wednesday' ? 'selected' : '' }}>Rabu</option>
                <option value="thursday" {{ ($user->weekly_review_day ?? 'sunday') === 'thursday' ? 'selected' : '' }}>Kamis</option>
                <option value="friday" {{ ($user->weekly_review_day ?? 'sunday') === 'friday' ? 'selected' : '' }}>Jumat</option>
                <option value="saturday" {{ ($user->weekly_review_day ?? 'sunday') === 'saturday' ? 'selected' : '' }}>Sabtu</option>
                <option value="sunday" {{ ($user->weekly_review_day ?? 'sunday') === 'sunday' ? 'selected' : '' }}>Minggu</option>
            </select>
        </div>

        {{-- Trade Result --}}
        <div class="flex items-center justify-between py-3">
            <div>
                <div class="text-white text-sm font-medium">Notifikasi Trade (EA Logger)</div>
                <div class="text-gray-400 text-xs mt-0.5">Push notification saat EA mengirim trade baru</div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="notify_trade_result" value="1"
                    {{ $user->notify_trade_result ? 'checked' : '' }}
                    class="sr-only peer">
                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500"></div>
            </label>
        </div>
    </div>

    {{-- Push Subscription --}}
    <div class="card p-4 sm:p-5 mb-4">
        <h2 class="text-white font-semibold mb-3">
            <i class="fas fa-mobile-alt mr-2 text-cyan-400"></i>Push Browser
        </h2>
        <p class="text-gray-400 text-xs mb-3">Aktifkan notifikasi push di browser ini.</p>
        <button type="button" id="push-toggle-btn" 
            class="btn-secondary px-4 py-2 rounded-lg text-sm font-medium"
            onclick="togglePushNotification()">
            <i class="fas fa-bell mr-2"></i><span id="push-btn-text">Aktifkan Push</span>
        </button>
        <div id="push-status" class="text-xs mt-2 text-gray-500"></div>
    </div>

    <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-semibold">
        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
    </button>
</form>

<script>
// Toggle daily time visibility
document.querySelector('input[name="notify_daily_journal"]').addEventListener('change', function() {
    document.getElementById('daily-time-section').classList.toggle('hidden', !this.checked);
});

// Toggle weekly day visibility
document.querySelector('input[name="notify_weekly_review"]').addEventListener('change', function() {
    document.getElementById('weekly-day-section').classList.toggle('hidden', !this.checked);
});

// Push notification toggle
async function togglePushNotification() {
    if (!('serviceWorker' in navigator)) {
        document.getElementById('push-status').textContent = 'Browser tidak mendukung push notification.';
        return;
    }

    if (!('PushManager' in window)) {
        document.getElementById('push-status').textContent = 'PushManager tidak tersedia.';
        return;
    }

    const statusEl = document.getElementById('push-status');
    const btnText = document.getElementById('push-btn-text');

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            statusEl.textContent = 'Izin notifikasi ditolak.';
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            // Unsubscribe
            await subscription.unsubscribe();
            await fetch('{{ route("notifications.unsubscribe") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ endpoint: subscription.endpoint })
            });
            btnText.textContent = 'Aktifkan Push';
            statusEl.textContent = 'Push notification dinonaktifkan.';
        } else {
            // Subscribe
            const response = await fetch('{{ route("notifications.subscribe") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ placeholder: true })
            });
            const data = await response.json();
            
            const newSubscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: data.vapid_key
            });

            await fetch('{{ route("notifications.subscribe") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({
                    endpoint: newSubscription.endpoint,
                    keys: { auth: newSubscription.getKey('auth'), p256dh: newSubscription.getKey('p256dh') }
                })
            });
            btnText.textContent = 'Nonaktifkan Push';
            statusEl.textContent = 'Push notification aktif!';
        }
    } catch (err) {
        statusEl.textContent = 'Error: ' + err.message;
    }
}
</script>
@endsection