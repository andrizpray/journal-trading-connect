<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="apple-mobile-web-app-capable" content="yes">

        <title>{{ config('app.name', 'Journal Trading Connect') }}</title>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
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
            .btn-primary {
                background: linear-gradient(135deg, #06b6d4, #0891b2);
                color: white;
                transition: all 0.2s;
            }
            .btn-primary:hover {
                background: linear-gradient(135deg, #22d3ee, #06b6d4);
                box-shadow: 0 0 20px rgba(6, 182, 212, 0.3);
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            {{-- Logo --}}
            <div class="mb-8 text-center">
                <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background: linear-gradient(135deg, #06b6d4, #10b981);">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <h1 class="text-xl font-bold text-white">Journal Trading Connect</h1>
                <p class="text-xs mt-1" style="color: #9ca3af;">Import & analisis riwayat trading Anda</p>
            </div>

            {{-- Form Card --}}
            <div class="w-full sm:max-w-md p-6 sm:p-8 rounded-2xl" style="background-color: #1f2937; border: 1px solid #374151;">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <p class="text-xs mt-6" style="color: #4b5563;">
                &copy; {{ date('Y') }} Journal Trading Connect
            </p>
        </div>
    </body>
</html>
