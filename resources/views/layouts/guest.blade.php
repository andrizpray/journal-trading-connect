<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="apple-mobile-web-app-capable" content="yes">

        <title>{{ config('app.name', 'Journal Trading Connect') }}</title>

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#06b6d4">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="JTC">
        <link rel="apple-touch-icon" href="/pwa-icons/icon-192.png">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:wght@300;400;500;600;700;800;900&family=space+mono:wght@400;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Service Worker -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').catch(function() {});
                });
            }
        </script>

        <style>
            /* ========== DARK THEME (default for guest) ========== */
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #030712 0%, #0a0f1a 50%, #111827 100%);
                color: #f9fafb;
            }
            input, select, textarea { font-size: 16px !important; }
            .dark-input {
                background-color: #111827;
                border: 1px solid #374151;
                color: #f9fafb;
                border-radius: 0.5rem;
                padding: 0.6rem 0.75rem;
                transition: border-color 0.2s;
                width: 100%;
            }
            .dark-input:focus {
                border-color: #06b6d4;
                outline: none;
                box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
            }
            .dark-input::placeholder { color: #6b7280; }

            /* Password toggle */
            .password-toggle-wrap { position: relative; }
            .password-toggle-wrap input { padding-right: 2.5rem; }
            .password-toggle-btn {
                position: absolute;
                right: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
                color: #6b7280;
                cursor: pointer;
                padding: 0.25rem;
                transition: color 0.2s;
                background: none;
                border: none;
            }
            .password-toggle-btn:hover { color: #9ca3af; }

            .theme-dark .btn-primary {
                background: linear-gradient(135deg, #06b6d4, #0891b2);
                color: white;
                transition: all 0.2s;
            }
            .theme-dark .btn-primary:hover {
                background: linear-gradient(135deg, #22d3ee, #06b6d4);
                box-shadow: 0 0 20px rgba(6, 182, 212, 0.3);
            }

            .theme-dark .guest-card {
                background-color: #1f2937;
                border: 1px solid #374151;
                border-radius: 1rem;
            }
            .theme-dark .guest-logo {
                background: linear-gradient(135deg, #06b6d4, #10b981);
                border-radius: 0.75rem;
            }
            .theme-dark .guest-title { color: white; }
            .theme-dark .guest-subtitle { color: #9ca3af; }
            .theme-dark .guest-footer { color: #4b5563; }

            /* ========== NEOBRUTALISM THEME ========== */
            .theme-neobrutalism {
                --neo-bg: #FFF8F0;
                --neo-card: #FFFFFF;
                --neo-border: #1a1a2e;
                --neo-text: #1a1a2e;
                --neo-muted: #5a5a7a;
                --neo-accent: #FF6B35;
                --neo-green: #2EC4B6;
                --neo-red: #E71D36;
                --neo-yellow: #FF9F1C;
            }

            .theme-neobrutalism {
                font-family: 'Inter', sans-serif;
                background: var(--neo-bg);
                color: var(--neo-text);
            }

            .theme-neobrutalism .guest-card {
                background: var(--neo-card);
                border: 3px solid var(--neo-border);
                border-radius: 0;
                box-shadow: 8px 8px 0px var(--neo-border);
            }

            .theme-neobrutalism .guest-logo {
                background: var(--neo-accent);
                border: 3px solid var(--neo-border);
                border-radius: 0;
                box-shadow: 4px 4px 0px var(--neo-border);
            }

            .theme-neobrutalism .guest-title {
                color: var(--neo-text);
                font-weight: 900;
            }
            .theme-neobrutalism .guest-subtitle {
                color: var(--neo-muted);
                font-weight: 600;
            }
            .theme-neobrutalism .guest-footer {
                color: var(--neo-muted);
                font-weight: 600;
            }

            .theme-neobrutalism .dark-input {
                background-color: white;
                border: 3px solid var(--neo-border);
                color: var(--neo-text);
                border-radius: 0;
                font-weight: 600;
            }
            .theme-neobrutalism .dark-input:focus {
                border-color: var(--neo-accent);
                outline: none;
                box-shadow: 4px 4px 0px rgba(255, 107, 53, 0.3);
            }
            .theme-neobrutalism .dark-input::placeholder {
                color: #b0b0c0;
                font-weight: 400;
            }

            .theme-neobrutalism .btn-primary {
                background: var(--neo-accent);
                color: white;
                border: 3px solid var(--neo-border);
                border-radius: 0;
                box-shadow: 4px 4px 0px var(--neo-border);
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .theme-neobrutalism .btn-primary:hover {
                background: #ff8c5a;
                box-shadow: 2px 2px 0px var(--neo-border);
                transform: translate(2px, 2px);
            }
            .theme-neobrutalism .btn-primary:active {
                box-shadow: 0px 0px 0px var(--neo-border);
                transform: translate(4px, 4px);
            }

            .theme-neobrutalism .password-toggle-btn {
                color: var(--neo-muted);
            }
            .theme-neobrutalism .password-toggle-btn:hover {
                color: var(--neo-accent);
            }

            /* Theme switcher on guest page */
            .theme-switcher-guest {
                position: fixed;
                bottom: 1rem;
                right: 1rem;
                display: flex;
                gap: 0.5rem;
                z-index: 50;
            }
            .theme-switcher-guest button {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #374151;
                border-radius: 50%;
                cursor: pointer;
                transition: all 0.2s;
                font-size: 14px;
            }
            .theme-switcher-guest .switch-dark {
                background: #1f2937;
                color: white;
            }
            .theme-switcher-guest .switch-dark.active {
                border-color: #06b6d4;
                box-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
            }
            .theme-switcher-guest .switch-neo {
                background: #FFF8F0;
                color: #1a1a2e;
                border-color: #1a1a2e;
            }
            .theme-switcher-guest .switch-neo.active {
                border-color: #FF6B35;
                box-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
            }
        </style>
    </head>
    <body class="antialiased theme-dark">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            {{-- Logo --}}
            <div class="mb-8 text-center">
                <div class="guest-logo w-14 h-14 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <h1 class="guest-title text-xl font-bold">Journal Trading Connect</h1>
                <p class="guest-subtitle text-xs mt-1">Import & analisis riwayat trading Anda</p>
            </div>

            {{-- Form Card --}}
            <div class="guest-card w-full sm:max-w-md p-6 sm:p-8">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <p class="guest-footer text-xs mt-6">
                &copy; {{ date('Y') }} Journal Trading Connect
            </p>
        </div>

        {{-- Theme switcher (guest) --}}
        <div class="theme-switcher-guest">
            <button class="switch-dark active" onclick="switchGuestTheme('dark')" title="Dark Theme">
                <i class="fas fa-moon"></i>
            </button>
            <button class="switch-neo" onclick="switchGuestTheme('neobrutalism')" title="Neobrutalism Theme">
                <i class="fas fa-palette"></i>
            </button>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Restore guest theme from localStorage
            const saved = localStorage.getItem('guest-theme');
            if (saved) switchGuestTheme(saved, true);

            document.querySelectorAll('.password-toggle-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('input');
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('fa-eye', 'fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('fa-eye-slash', 'fa-eye');
                    }
                });
            });
        });

        function switchGuestTheme(theme, silent) {
            document.body.className = 'antialiased theme-' + theme;
            localStorage.setItem('guest-theme', theme);

            document.querySelectorAll('.theme-switcher-guest button').forEach(btn => {
                btn.classList.remove('active');
            });
            if (theme === 'dark') {
                document.querySelector('.switch-dark').classList.add('active');
            } else {
                document.querySelector('.switch-neo').classList.add('active');
            }
        }
        </script>
    </body>
</html>
