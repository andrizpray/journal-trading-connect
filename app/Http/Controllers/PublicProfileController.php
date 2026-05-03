<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TradingAccount;
use App\Models\TradeHistory;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PublicProfileController extends Controller
{
    /**
     * View public profile by slug (no auth required)
     */
    public function show($slug)
    {
        $user = User::where('public_slug', $slug)
            ->where('public_profile_enabled', true)
            ->firstOrFail();

        $visibleFields = $user->public_visible_fields ?? [];

        // Base trade query
        $tradeQuery = TradeHistory::where('user_id', $user->id);

        // Stats
        $totalTrades = (clone $tradeQuery)->count();
        $totalWins = (clone $tradeQuery)->where('result', 'win')->count();
        $totalLosses = (clone $tradeQuery)->where('result', 'loss')->count();
        $winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;
        $totalPnl = (clone $tradeQuery)->sum('profit_loss');

        // Equity curve data
        $equityData = (clone $tradeQuery)
            ->whereNotNull('close_date')
            ->orderBy('close_date')
            ->get(['close_date', 'profit_loss']);

        $equityDates = [];
        $equityValues = [];
        $cumulative = 0;
        foreach ($equityData as $trade) {
            $cumulative += $trade->profit_loss;
            $equityDates[] = $trade->close_date->format('d M Y');
            $equityValues[] = round($cumulative, 2);
        }

        // Pair performance
        $pairStats = (clone $tradeQuery)
            ->selectRaw('currency_pair, COUNT(*) as trades, SUM(profit_loss) as pnl')
            ->groupBy('currency_pair')
            ->orderByDesc('pnl')
            ->take(8)
            ->get();

        // Risk metrics (if visible)
        $avgWin = (clone $tradeQuery)->where('result', 'win')->avg('profit_loss') ?: 0;
        $avgLoss = (clone $tradeQuery)->where('result', 'loss')->avg('profit_loss') ?: 0;
        $profitFactor = $avgLoss != 0 ? round(abs($avgWin / $avgLoss), 2) : 0;

        // Max drawdown
        $maxDrawdown = 0;
        $peak = 0;
        $cumDD = 0;
        foreach ($equityData as $trade) {
            $cumDD += $trade->profit_loss;
            if ($cumDD > $peak) $peak = $cumDD;
            $dd = $peak - $cumDD;
            if ($dd > $maxDrawdown) $maxDrawdown = $dd;
        }

        // Recent trades (if visible)
        $recentTrades = [];
        if (in_array('recent_trades', $visibleFields)) {
            $recentTrades = (clone $tradeQuery)
                ->with('tradingAccount')
                ->orderBy('close_date', 'desc')
                ->take(20)
                ->get();
        }

        return view('public-profile.show', compact(
            'user', 'visibleFields',
            'totalTrades', 'totalWins', 'totalLosses', 'winRate', 'totalPnl',
            'equityDates', 'equityValues', 'pairStats',
            'avgWin', 'avgLoss', 'profitFactor', 'maxDrawdown',
            'recentTrades',
        ));
    }

    /**
     * Public profile settings page (auth required)
     */
    public function settings()
    {
        $user = Auth::user();
        return view('public-profile.settings', compact('user'));
    }

    /**
     * Update public profile settings
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'public_profile_enabled' => 'boolean',
            'public_visible_fields' => 'array',
            'public_visible_fields.*' => 'string|in:total_trades,win_rate,total_pnl,equity_curve,pair_performance,risk_metrics,recent_trades',
        ]);

        // Generate slug if enabling and no slug exists
        if (!empty($validated['public_profile_enabled']) && !$user->public_slug) {
            $slug = Str::lower(Str::slug($user->name) . '-' . Str::random(6));
            // Ensure unique
            while (User::where('public_slug', $slug)->exists()) {
                $slug = Str::lower(Str::slug($user->name) . '-' . Str::random(6));
            }
            $user->public_slug = $slug;
        }

        // If disabling, clear slug
        if (empty($validated['public_profile_enabled'])) {
            $user->public_slug = null;
            $user->public_visible_fields = null;
            $user->public_profile_enabled = false;
        } else {
            $user->public_profile_enabled = true;
            $user->public_visible_fields = $validated['public_visible_fields'] ?? [];
        }

        $user->save();

        return back()->with('success', 'Pengaturan public profile berhasil diperbarui.');
    }

    /**
     * Regenerate slug
     */
    public function regenerateSlug()
    {
        $user = Auth::user();
        $slug = Str::lower(Str::slug($user->name) . '-' . Str::random(6));
        while (User::where('public_slug', $slug)->exists()) {
            $slug = Str::lower(Str::slug($user->name) . '-' . Str::random(6));
        }
        $user->public_slug = $slug;
        $user->save();

        return back()->with('success', 'Link publik berhasil diperbarui.');
    }
}
