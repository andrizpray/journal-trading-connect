<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-lg font-semibold text-white mb-6 text-center">Masuk ke Akun</h2>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label class="block text-xs font-medium mb-1.5" style="color: #9ca3af;">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="dark-input" placeholder="email@contoh.com">
            @error('email')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-5">
            <label class="block text-xs font-medium mb-1.5" style="color: #9ca3af;">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="dark-input" placeholder="Masukkan password">
            @error('password')
                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember --}}
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-cyan-500 focus:ring-cyan-500">
                <span class="text-xs" style="color: #9ca3af;">Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-cyan-400 hover:text-cyan-300">Lupa password?</a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full py-2.5 rounded-lg text-sm font-semibold">
            <i class="fas fa-sign-in-alt mr-1"></i>Masuk
        </button>
    </form>

    {{-- Register Link --}}
    <div class="text-center mt-6">
        <span class="text-xs" style="color: #6b7280;">Belum punya akun?</span>
        <a href="{{ route('register') }}" class="text-xs text-cyan-400 hover:text-cyan-300 ml-1 font-medium">Daftar sekarang</a>
    </div>
</x-guest-layout>
