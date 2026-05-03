<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TradingAccountController;
use App\Http\Controllers\TradeHistoryController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Trading Accounts
    Route::get('/trading-accounts', [TradingAccountController::class, 'index'])->name('trading-accounts.index');
    Route::post('/trading-accounts', [TradingAccountController::class, 'store'])->name('trading-accounts.store');
    Route::put('/trading-accounts/{id}', [TradingAccountController::class, 'update'])->name('trading-accounts.update');
    Route::delete('/trading-accounts/{id}', [TradingAccountController::class, 'destroy'])->name('trading-accounts.destroy');

    // Trade History
    Route::get('/trade-history', [TradeHistoryController::class, 'index'])->name('trade-history.index');
    Route::get('/trade-history/export', [TradeHistoryController::class, 'export'])->name('trade-history.export');
    Route::get('/trade-history/{id}/edit', [TradeHistoryController::class, 'edit'])->name('trade-history.edit');
    Route::put('/trade-history/{id}', [TradeHistoryController::class, 'update'])->name('trade-history.update');
    Route::delete('/trade-history/{id}', [TradeHistoryController::class, 'destroy'])->name('trade-history.destroy');

    // Journal
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    Route::get('/journal/{id}/edit', [JournalController::class, 'edit'])->name('journal.edit');
    Route::put('/journal/{id}', [JournalController::class, 'update'])->name('journal.update');
    Route::delete('/journal/{id}', [JournalController::class, 'destroy'])->name('journal.destroy');

    // CSV Import
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import', [ImportController::class, 'import'])->name('import.upload');
    Route::get('/import/logs', [ImportController::class, 'logs'])->name('import.logs');
    Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

require __DIR__.'/auth.php';
