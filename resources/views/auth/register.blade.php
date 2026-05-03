<x-guest-layout>
    <h2 class="text-lg font-semibold text-white mb-6 text-center">Buat Akun Baru</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div class="mb-4">
            <label class="block text-xs font-medium mb-1.5" style="color: #9ca3af;">Nama</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="dark-input" placeholder="Nama lengkap">
            @error('name')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label class="block text-xs font-medium mb-1.5" style="color: #9ca3af;">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   class="dark-input" placeholder="email@contoh.com">
            @error('email')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <label class="block text-xs font-medium mb-1.5" style="color: #9ca3af;">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="dark-input" placeholder="Min. 8 karakter">
            @error('password')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-5">
            <label class="block text-xs font-medium mb-1.5" style="color: #9ca3af;">Konfirmasi Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="dark-input" placeholder="Ulangi password">
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full py-2.5 rounded-lg text-sm font-semibold">
            <i class="fas fa-user-plus mr-1"></i>Daftar
        </button>
    </form>

    {{-- Login Link --}}
    <div class="text-center mt-6">
        <span class="text-xs" style="color: #6b7280;">Sudah punya akun?</span>
        <a href="{{ route('login') }}" class="text-xs text-cyan-400 hover:text-cyan-300 ml-1 font-medium">Masuk</a>
    </div>
</x-guest-layout>
