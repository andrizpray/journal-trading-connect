<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="apple-mobile-web-app-capable" content="yes">

        <title>{{ config('app.name', 'Journal Trading Connect') }}</title>

        <!-- Font Awesome 6.4 -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --bg-primary: #030712;
                --bg-secondary: #111827;
                --bg-card: #1f2937;
                --bg-sidebar: #0a0f1a;
                --border-color: #374151;
                --text-primary: #f9fafb;
                --text-secondary: #9ca3af;
                --accent-cyan: #06b6d4;
                --accent-green: #10b981;
                --accent-red: #ef4444;
                --accent-yellow: #f59e0b;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg-primary);
                color: var(--text-primary);
            }

            /* iOS zoom prevention */
            input, select, textarea {
                font-size: 16px !important;
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            ::-webkit-scrollbar-track {
                background: var(--bg-primary);
            }
            ::-webkit-scrollbar-thumb {
                background: var(--border-color);
                border-radius: 3px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: #4b5563;
            }

            /* Primary button gradient */
            .btn-primary {
                background: linear-gradient(135deg, #06b6d4, #0891b2);
                color: white;
                transition: all 0.2s;
            }
            .btn-primary:hover {
                background: linear-gradient(135deg, #22d3ee, #06b6d4);
                box-shadow: 0 0 20px rgba(6, 182, 212, 0.3);
            }

            .btn-secondary {
                background-color: #1f2937;
                border: 1px solid #374151;
                color: #d1d5db;
                transition: all 0.2s;
            }
            .btn-secondary:hover {
                background-color: #374151;
                color: white;
            }

            .btn-danger {
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: white;
                transition: all 0.2s;
            }
            .btn-danger:hover {
                background: linear-gradient(135deg, #f87171, #ef4444);
            }

            /* Card style */
            .card {
                background-color: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: 1rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            }

            /* Table row hover */
            .table-row-hover:hover {
                background-color: rgba(6, 182, 212, 0.05);
            }

            /* Sidebar active link */
            .nav-active {
                background: linear-gradient(90deg, rgba(6, 182, 212, 0.15), transparent);
                border-right: 3px solid var(--accent-cyan);
                color: var(--accent-cyan) !important;
            }

            /* Sidebar link */
            .nav-link-sidebar {
                color: var(--text-secondary);
                transition: all 0.2s;
            }
            .nav-link-sidebar:hover {
                background: rgba(6, 182, 212, 0.08);
                color: var(--text-primary);
            }

            /* Form inputs dark theme */
            .dark-input {
                background-color: #111827;
                border: 1px solid #374151;
                color: #f9fafb;
                border-radius: 0.5rem;
                padding: 0.5rem 0.75rem;
                transition: border-color 0.2s;
            }
            .dark-input:focus {
                border-color: var(--accent-cyan);
                outline: none;
                box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
            }
            .dark-input::placeholder {
                color: #6b7280;
            }

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

            /* Stat card gradient borders */
            .stat-card {
                position: relative;
                overflow: hidden;
            }
            .stat-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
            }
            .stat-card-cyan::before { background: linear-gradient(90deg, #06b6d4, transparent); }
            .stat-card-green::before { background: linear-gradient(90deg, #10b981, transparent); }
            .stat-card-yellow::before { background: linear-gradient(90deg, #f59e0b, transparent); }
            .stat-card-purple::before { background: linear-gradient(90deg, #8b5cf6, transparent); }

            /* Overlay for mobile sidebar */
            .sidebar-overlay {
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(2px);
            }

            /* Drag and drop zone */
            .drop-zone {
                border: 2px dashed #374151;
                transition: all 0.2s;
            }
            .drop-zone:hover, .drop-zone.active {
                border-color: var(--accent-cyan);
                background: rgba(6, 182, 212, 0.05);
            }

            /* Pagination dark */
            .pagination-dark a {
                color: #9ca3af;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                transition: all 0.2s;
            }
            .pagination-dark a:hover {
                background: #374151;
                color: white;
            }
            .pagination-dark .active a {
                background: linear-gradient(135deg, #06b6d4, #0891b2);
                color: white;
            }
        </style>
    </head>
    <body class="antialiased">
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

            <!-- Mobile sidebar overlay -->
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 z-40 sidebar-overlay lg:hidden"
            ></div>

            <!-- Sidebar -->
            <aside
                :class="{ '-translate-x-full lg:translate-x-0': !sidebarOpen, 'translate-x-0': sidebarOpen }"
                class="fixed lg:static inset-y-0 left-0 z-50 w-64 lg:w-72 flex-shrink-0 transition-transform duration-300 ease-in-out"
                style="background-color: var(--bg-sidebar);"
            >
                <div class="flex flex-col h-full">
                    <!-- Logo -->
                    <div class="flex items-center justify-between h-16 px-4 lg:px-6 border-b" style="border-color: var(--border-color);">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #06b6d4, #10b981);">
                                <i class="fas fa-chart-line text-white text-sm"></i>
                            </div>
                            <div>
                                <span class="font-bold text-white text-sm leading-none block">JTC</span>
                                <span class="text-xs leading-none" style="color: var(--text-secondary);">Trading Journal</span>
                            </div>
                        </a>
                        <!-- Close button mobile -->
                        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 py-4 px-3 overflow-y-auto">
                        <div class="space-y-1">
                            <a href="{{ route('dashboard') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                                <i class="fas fa-th-large w-5 text-center"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('trading-accounts.index') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('trading-accounts.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-wallet w-5 text-center"></i>
                                <span>Akun Trading</span>
                            </a>
                            <a href="{{ route('trade-history.index') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('trade-history.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-history w-5 text-center"></i>
                                <span>Riwayat Trade</span>
                            </a>
                            <a href="{{ route('analytics.index') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('analytics.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-chart-pie w-5 text-center"></i>
                                <span>Analytics</span>
                            </a>
                            <a href="{{ route('journal.index') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('journal.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-book-open w-5 text-center"></i>
                                <span>Jurnal Trading</span>
                            </a>
                            <a href="{{ route('import.index') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('import.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-file-import w-5 text-center"></i>
                                <span>Import CSV</span>
                            </a>
                            <a href="{{ route('connect.index') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('connect.*') ? 'nav-active' : '' }}">
                                <i class="fas fa-link w-5 text-center"></i>
                                <span>Connect</span>
                            </a>
                        </div>

                        <div class="mt-6 pt-4 border-t" style="border-color: var(--border-color);">
                            <div class="px-3 py-2">
                                <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary);">Menu</span>
                            </div>
                            <div class="space-y-1 mt-1">
                                @auth
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.*') ? 'nav-active' : '' }}">
                                            <i class="fas fa-shield-halved w-5 text-center text-amber-400"></i>
                                            <span>Admin Panel</span>
                                        </a>
                                    @endif
                                @endauth
                                <a href="{{ route('profile.edit') }}" class="nav-link-sidebar flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
                                    <i class="fas fa-cog w-5 text-center"></i>
                                    <span>Pengaturan</span>
                                </a>
                            </div>
                        </div>
                    </nav>

                    <!-- User section at bottom -->
                    <div class="p-3 border-t" style="border-color: var(--border-color);">
                        <div class="flex items-center gap-3 px-3 py-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold text-white" style="background: linear-gradient(135deg, #06b6d4, #8b5cf6);">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs truncate" style="color: var(--text-secondary);">{{ Auth::user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors" title="Keluar">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main content area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Top header bar -->
                <header class="h-16 flex items-center justify-between px-3 sm:px-6 border-b flex-shrink-0" style="background-color: var(--bg-secondary); border-color: var(--border-color);">
                    <!-- Hamburger button -->
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page title -->
                    <h1 class="hidden lg:block text-lg font-semibold text-white">
                        @yield('page-title', 'Dashboard')
                    </h1>

                    <!-- Right actions -->
                    <div class="flex items-center gap-3 ml-auto">
                        <span class="text-xs sm:text-sm" style="color: var(--text-secondary);">
                            <i class="fas fa-calendar-day mr-1"></i>
                            {{ now()->format('d M Y') }}
                        </span>
                    </div>
                </header>

                <!-- Page content -->
                <main class="flex-1 overflow-y-auto p-3 sm:p-4 lg:p-6">
                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 text-sm flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 px-4 py-3 rounded-lg bg-red-900/30 border border-red-700/50 text-red-400 text-sm flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
        @stack('scripts')
        {{-- Toast Container --}}
        <div id="toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none" style="max-width: 360px;"></div>
        <script>
        // Password toggle
        document.addEventListener('DOMContentLoaded', function() {
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

            // Auto-show toast from flash messages
            @if(session('success'))
            showToast('{{ session('success') }}', 'success');
            @endif
            @if(session('error'))
            showToast('{{ session('error') }}', 'error');
            @endif
        });

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-emerald-900/90 border-emerald-600/50 text-emerald-300',
                error: 'bg-red-900/90 border-red-600/50 text-red-300',
                info: 'bg-cyan-900/90 border-cyan-600/50 text-cyan-300',
            };
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle',
            };
            toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg border backdrop-blur-sm text-sm shadow-lg transform transition-all duration-300 translate-x-full opacity-0 ${colors[type] || colors.info}`;
            toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span class="flex-1">${message}</span><button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100"><i class="fas fa-times text-xs"></i></button>`;
            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
        </script>
    </body>
</html>
