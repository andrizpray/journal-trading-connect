<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('ea-api', function (Request $request) {
            $token = (string) $request->bearerToken();
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(180)->by('ea-token:' . hash('sha256', $token)),
                Limit::perMinute(300)->by('ea-ip:' . $ip),
            ];
        });
    }
}
