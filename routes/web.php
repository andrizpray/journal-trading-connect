<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TradingAccountController;
use App\Http\Controllers\TradeHistoryController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ConnectController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// Public profile (no auth required)
Route::get('/u/{slug}', [PublicProfileController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('public-profile.show');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');

    // Trading Accounts
    Route::get('/trading-accounts', [TradingAccountController::class, 'index'])->name('trading-accounts.index');
    Route::post('/trading-accounts', [TradingAccountController::class, 'store'])->name('trading-accounts.store');
    Route::put('/trading-accounts/{id}', [TradingAccountController::class, 'update'])->name('trading-accounts.update');
    Route::delete('/trading-accounts/{id}', [TradingAccountController::class, 'destroy'])->name('trading-accounts.destroy');

    // Trade History
    Route::get('/trade-history', [TradeHistoryController::class, 'index'])->name('trade-history.index');
    Route::get('/trade-history/create', [TradeHistoryController::class, 'create'])->name('trade-history.create');
    Route::post('/trade-history', [TradeHistoryController::class, 'store'])->name('trade-history.store');
    Route::get('/trade-history/export', [TradeHistoryController::class, 'export'])->name('trade-history.export')->middleware('throttle:20,1');
    Route::get('/trade-history/{id}/edit', [TradeHistoryController::class, 'edit'])->name('trade-history.edit');
    Route::put('/trade-history/{id}', [TradeHistoryController::class, 'update'])->name('trade-history.update');
    Route::delete('/trade-history/{id}', [TradeHistoryController::class, 'destroy'])->name('trade-history.destroy');

    // Journal
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::get('/journal/review', [JournalController::class, 'review'])->name('journal.review');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    Route::post('/journal/{id}/screenshot', [JournalController::class, 'uploadScreenshot'])->name('journal.screenshot');
    Route::delete('/journal/{id}/screenshot', [JournalController::class, 'deleteScreenshot'])->name('journal.delete-screenshot');
    Route::get('/journal/{id}/edit', [JournalController::class, 'edit'])->name('journal.edit');
    Route::put('/journal/{id}', [JournalController::class, 'update'])->name('journal.update');
    Route::delete('/journal/{id}', [JournalController::class, 'destroy'])->name('journal.destroy');

    // CSV Import
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import', [ImportController::class, 'import'])->name('import.upload')->middleware('throttle:10,1');
    Route::get('/import/logs', [ImportController::class, 'logs'])->name('import.logs');
    Route::get('/import/template', [ImportController::class, 'template'])->name('import.template');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::post('/leaderboard/toggle', [LeaderboardController::class, 'toggleOptIn'])->name('leaderboard.toggle');

    // Public Profile Settings
    Route::get('/public-profile', [PublicProfileController::class, 'settings'])->name('public-profile.settings');
    Route::patch('/public-profile', [PublicProfileController::class, 'update'])->name('public-profile.update');
    Route::post('/public-profile/regenerate-slug', [PublicProfileController::class, 'regenerateSlug'])->name('public-profile.regenerate-slug');

    // Connect
    Route::get('/connect', [ConnectController::class, 'index'])->name('connect.index');
    Route::post('/connect/jurnal-trading', [ConnectController::class, 'importFromJurnalTrading'])->name('connect.import-jurnal');
    Route::get('/connect/ea-logger', [ConnectController::class, 'eaLoggerSetup'])->name('connect.ea-logger');
    Route::post('/connect/ea-logger/token/{id}', [ConnectController::class, 'regenerateToken'])->name('connect.ea-logger.regenerate-token');
    Route::post('/connect/ea-logger/test', [ConnectController::class, 'testConnection'])->name('connect.ea-logger.test');
    Route::post('/connect/ea-logger/test-ajax', [ConnectController::class, 'testConnectionAjax'])->name('connect.ea-logger.test-ajax');

    // Admin (only accessible by admin users)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::patch('/users/{id}/role', [AdminController::class, 'updateUserRole'])->name('update-role');
        Route::get('/users/{id}', [AdminController::class, 'viewUser'])->name('user-detail');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('delete-user');
    });

    // Notifications
    Route::get('/notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
    Route::post('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.update');
    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe'])->name('notifications.subscribe');
    Route::post('/notifications/unsubscribe', [NotificationController::class, 'unsubscribe'])->name('notifications.unsubscribe');
});

require __DIR__.'/auth.php';
