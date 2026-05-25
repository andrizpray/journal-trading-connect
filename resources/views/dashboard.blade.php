<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">

            {{-- Welcome --}}
            <div class="mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                    Welcome back, {{ Auth::user()->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-400">Here's a snapshot of your trading performance.</p>
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5">

                {{-- Total P&L --}}
                <div class="relative rounded-2xl p-[1px] bg-gradient-to-br from-cyan-500/60 via-cyan-500/10 to-transparent shadow-lg">
                    <div class="relative h-full rounded-2xl bg-gray-900/80 backdrop-blur-sm p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total P&amp;L</p>
                                <p class="mt-2 text-2xl font-bold text-white">$0.00</p>
                                <p class="mt-1 text-xs text-cyan-400 flex items-center gap-1">
                                    <i class="fas fa-arrow-trend-up"></i>
                                    <span>All time</span>
                                </p>
                            </div>
                            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-cyan-500/15 text-cyan-400 ring-1 ring-cyan-500/20">
                                <i class="fas fa-sack-dollar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Win Rate --}}
                <div class="relative rounded-2xl p-[1px] bg-gradient-to-br from-emerald-500/60 via-emerald-500/10 to-transparent shadow-lg">
                    <div class="relative h-full rounded-2xl bg-gray-900/80 backdrop-blur-sm p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Win Rate</p>
                                <p class="mt-2 text-2xl font-bold text-white">0%</p>
                                <p class="mt-1 text-xs text-emerald-400 flex items-center gap-1">
                                    <i class="fas fa-bullseye"></i>
                                    <span>0 / 0 trades</span>
                                </p>
                            </div>
                            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/20">
                                <i class="fas fa-chart-line"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Total Trades --}}
                <div class="relative rounded-2xl p-[1px] bg-gradient-to-br from-amber-500/60 via-amber-500/10 to-transparent shadow-lg">
                    <div class="relative h-full rounded-2xl bg-gray-900/80 backdrop-blur-sm p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total Trades</p>
                                <p class="mt-2 text-2xl font-bold text-white">0</p>
                                <p class="mt-1 text-xs text-amber-400 flex items-center gap-1">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Across all accounts</span>
                                </p>
                            </div>
                            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/20">
                                <i class="fas fa-clock-rotate-left"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Active Accounts --}}
                <div class="relative rounded-2xl p-[1px] bg-gradient-to-br from-violet-500/60 via-violet-500/10 to-transparent shadow-lg">
                    <div class="relative h-full rounded-2xl bg-gray-900/80 backdrop-blur-sm p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Active Accounts</p>
                                <p class="mt-2 text-2xl font-bold text-white">0</p>
                                <p class="mt-1 text-xs text-violet-400 flex items-center gap-1">
                                    <i class="fas fa-plug"></i>
                                    <span>Connected</span>
                                </p>
                            </div>
                            <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-violet-500/15 text-violet-400 ring-1 ring-violet-500/20">
                                <i class="fas fa-wallet"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick panel --}}
            <div class="mt-8 rounded-2xl border border-gray-800 bg-gray-900/60 backdrop-blur-sm p-6 text-gray-200">
                {{ __("You're logged in!") }}
            </div>
        </div>
    </div>
</x-app-layout>
