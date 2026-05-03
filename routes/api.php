<?php

use App\Http\Controllers\EaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EA Logger API Routes
|--------------------------------------------------------------------------
| Endpoint untuk menerima data dari EA Logger (MT4/MT5).
| Auth menggunakan Bearer token (api_token dari trading_accounts).
|
*/

Route::middleware('ea.token')->group(function () {
    // Kirim satu trade
    Route::post('/ea/trade', [EaApiController::class, 'storeTrade']);

    // Kirim batch trades (max 50)
    Route::post('/ea/trade/batch', [EaApiController::class, 'storeBatch']);

    // Heartbeat — EA kirim sinyal masih hidup
    Route::post('/ea/heartbeat', [EaApiController::class, 'heartbeat']);

    // EA minta konfigurasi
    Route::get('/ea/config', [EaApiController::class, 'getConfig']);
});
