@extends('layouts.app')
@section('page-title', 'Pengaturan')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-white">
        <i class="fas fa-cog mr-2 text-cyan-400"></i>Pengaturan
    </h1>
    <p class="text-sm mt-1" style="color: var(--text-secondary);">Kelola profil & keamanan akun</p>
</div>

<div class="max-w-2xl space-y-4">
    {{-- Profile Information --}}
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-semibold text-white mb-1">
            <i class="fas fa-user mr-2 text-purple-400"></i>Informasi Profil
        </h3>
        <p class="text-xs mb-5" style="color: var(--text-secondary);">Update nama dan email akun Anda.</p>

        <form method="post" action="{{ route('profile.update') }}">
            @csrf @method('patch')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                           class="dark-input w-full">
                    @error('name')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="dark-input w-full">
                    @error('email')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <p class="text-xs mt-2 text-yellow-400">
                            Email belum terverifikasi.
                            <form method="post" action="{{ route('verification.send') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-cyan-400 hover:text-cyan-300 underline">Kirim ulang</button>
                            </form>
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <p class="text-xs mt-1 text-emerald-400">Link verifikasi baru sudah dikirim!</p>
                        @endif
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 mt-5">
                <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
                @if (session('status') === 'profile-updated')
                    <span class="text-xs text-emerald-400">
                        <i class="fas fa-check mr-1"></i>Tersimpan!
                    </span>
                @endif
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-semibold text-white mb-1">
            <i class="fas fa-lock mr-2 text-yellow-400"></i>Ubah Password
        </h3>
        <p class="text-xs mb-5" style="color: var(--text-secondary);">Pastikan akun Anda menggunakan password yang kuat.</p>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Password Saat Ini</label>
                    <div class="password-toggle-wrap">
                        <input type="password" name="current_password" required autocomplete="current-password"
                               class="dark-input w-full">
                        <button type="button" class="password-toggle-btn"><i class="fas fa-eye"></i></button>
                    </div>
                    @error('current_password')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Password Baru</label>
                    <div class="password-toggle-wrap">
                        <input type="password" name="password" required autocomplete="new-password"
                               class="dark-input w-full">
                        <button type="button" class="password-toggle-btn"><i class="fas fa-eye"></i></button>
                    </div>
                    @error('password')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Konfirmasi Password Baru</label>
                    <div class="password-toggle-wrap">
                        <input type="password" name="password_confirmation" required autocomplete="new-password"
                               class="dark-input w-full">
                        <button type="button" class="password-toggle-btn"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg text-sm font-medium">
                    <i class="fas fa-key mr-1"></i>Ubah Password
                </button>
            </div>
        </form>
    </div>

    {{-- Delete Account --}}
    <div class="card p-5 sm:p-6 border-red-900/30">
        <h3 class="text-sm font-semibold text-red-400 mb-1">
            <i class="fas fa-exclamation-triangle mr-2"></i>Hapus Akun
        </h3>
        <p class="text-xs mb-4" style="color: var(--text-secondary);">Setelah akun dihapus, semua data akan hilang permanen.</p>
        <form method="post" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
            @csrf
            @method('delete')
            <button type="button" onclick="if(confirm('Yakin ingin menghapus akun? Semua data akan hilang permanen.')) document.getElementById('deleteAccountForm').submit();"
                    class="px-4 py-2 rounded-lg text-xs font-medium bg-red-900/20 border border-red-800/50 text-red-400 hover:bg-red-900/40 transition-colors">
                <i class="fas fa-trash mr-1"></i>Hapus Akun
            </button>
        </form>
    </div>
</div>
@endsection
