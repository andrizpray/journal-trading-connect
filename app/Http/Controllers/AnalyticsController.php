<?php

namespace App\Http\Controllers;

use App\Models\TradeHistory;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $accountId = $request->filled('account') ? (int) $request->account : null;

        // Base query
        $query = TradeHistory::where('user_id', $user->id);
        if ($accountId) {
            $query->where('trading_account_id', $accountId);
        }

        // ==============================
        // 2.1 — Overview Stats
        // ==============================
        $totalTrades = (clone $query)->count();
        $totalWins = (clone $query)->where('result', 'win')->count();
        $totalLosses = (clone $query)->where('result', 'loss')->count();
        $totalBE = (clone $query)->where('result', 'break_even')->count();
        $winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;
        $totalPnl = (clone $query)->sum('profit_loss');
        $avgPnl = $totalTrades > 0 ? round($totalPnl / $totalTrades, 2) : 0;
        $avgDuration = (clone $query)->whereNotNull('duration_minutes')->avg('duration_minutes');

        // Best & worst pair
        $pairStats = (clone $query)
            ->selectRaw('currency_pair, COUNT(*) as trades, SUM(profit_loss) as total_pnl, AVG(profit_loss) as avg_pnl')
            ->groupBy('currency_pair')
            ->orderByDesc('total_pnl')
            ->get();

        $bestPair = $pairStats->first();
        $worstPair = $pairStats->where('total_pnl', '<', 0)->sortBy('total_pnl')->first();

        // ==============================
        // 2.2 — Pair Performance (bar chart)
        // ==============================
        $pairChartData = $pairStats
            ->sortByDesc('total_pnl')
            ->take(10)
            ->values();

        // ==============================
        // 2.3 — Equity Curve
        // ==============================
        $equityData = (clone $query)
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

        // ==============================
        // 2.4 — Heatmap: day × hour
        // ==============================
        $heatmapTrades = (clone $query)
            ->whereNotNull('close_date')
            ->get(['close_date', 'profit_loss']);

        $heatmapRawGrouped = collect();
        foreach ($heatmapTrades as $trade) {
            // MySQL DAYOFWEEK is 1=Sunday, Carbon dayOfWeek is 0=Sunday
            $dow = $trade->close_date->dayOfWeek + 1;
            $hour = $trade->close_date->hour;
            
            $key = $dow . '-' . $hour;
            if (!isset($heatmapRawGrouped[$key])) {
                $heatmapRawGrouped[$key] = (object)['dow' => $dow, 'hour' => $hour, 'trades' => 0, 'pnl' => 0];
            }
            $heatmapRawGrouped[$key]->trades++;
            $heatmapRawGrouped[$key]->pnl += $trade->profit_loss;
        }
        $heatmapRaw = $heatmapRawGrouped->values();

        // Build 7×24 matrix
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $heatmap = [];
        $maxTrades = 1;
        for ($d = 1; $d <= 7; $d++) {
            $heatmap[$d] = [];
            for ($h = 0; $h < 24; $h++) {
                $cell = $heatmapRaw->firstWhere(fn($r) => (int) $r->dow === $d && (int) $r->hour === $h);
                $count = $cell ? (int) $cell->trades : 0;
                $pnl = $cell ? (float) $cell->pnl : 0;
                $heatmap[$d][$h] = ['trades' => $count, 'pnl' => $pnl];
                if ($count > $maxTrades) $maxTrades = $count;
            }
        }

        // ==============================
        // 2.5 — Streak Tracker
        // ==============================
        $tradesSorted = (clone $query)
            ->whereNotNull('close_date')
            ->orderBy('close_date')
            ->pluck('result');

        $maxWinStreak = 0;
        $maxLossStreak = 0;
        $currentWin = 0;
        $currentLoss = 0;

        foreach ($tradesSorted as $result) {
            if ($result === 'win') {
                $currentWin++;
                $currentLoss = 0;
                if ($currentWin > $maxWinStreak) $maxWinStreak = $currentWin;
            } elseif ($result === 'loss') {
                $currentLoss++;
                $currentWin = 0;
                if ($currentLoss > $maxLossStreak) $maxLossStreak = $currentLoss;
            } else {
                $currentWin = 0;
                $currentLoss = 0;
            }
        }

        // Current streak
        $currentStreak = 0;
        $currentStreakType = '';
        foreach ($tradesSorted->reverse() as $result) {
            if ($result === 'win' || $result === 'loss') {
                if (empty($currentStreakType)) {
                    $currentStreakType = $result;
                    $currentStreak = 1;
                } elseif ($result === $currentStreakType) {
                    $currentStreak++;
                } else {
                    break;
                }
            } else {
                break; // break_even breaks the streak
            }
        }

        // ==============================
        // 2.6 — Risk Metrics
        // ==============================
        $avgWin = (clone $query)->where('result', 'win')->avg('profit_loss') ?: 0;
        $avgLoss = (clone $query)->where('result', 'loss')->avg('profit_loss') ?: 0;
        $profitFactor = $avgLoss != 0 ? round(abs($avgWin / $avgLoss), 2) : 0;
        $maxWin = (clone $query)->where('result', 'win')->max('profit_loss') ?: 0;
        $maxLoss = (clone $query)->where('result', 'loss')->min('profit_loss') ?: 0;

        // Max Drawdown (from equity curve)
        $maxDrawdown = 0;
        $peak = 0;
        $cumDD = 0;
        foreach ($equityData as $trade) {
            $cumDD += $trade->profit_loss;
            if ($cumDD > $peak) $peak = $cumDD;
            $dd = $peak - $cumDD;
            if ($dd > $maxDrawdown) $maxDrawdown = $dd;
        }

        // Daily Drawdown (per hari, terburuk 10)
        $dailyPnlRaw = (clone $query)
            ->whereNotNull('close_date')
            ->selectRaw('DATE(close_date) as date, SUM(profit_loss) as pnl')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate cumulative equity per day, then drawdown + daily P&L
        $dailyCumPnl = [];
        $cumPnl = 0;
        foreach ($dailyPnlRaw as $row) {
            $cumPnl += $row->pnl;
            $dailyCumPnl[] = [
                'date' => $row->date,
                'cum' => $cumPnl,
                'daily_pnl' => round($row->pnl, 2),
            ];
        }

        $dailyDrawdownList = [];
        $ddHighWater = 0;
        foreach ($dailyCumPnl as $entry) {
            if ($entry['cum'] > $ddHighWater) {
                $ddHighWater = $entry['cum'];
            }
            $dd = $ddHighWater - $entry['cum'];
            $dailyDrawdownList[] = [
                'date' => $entry['date'],
                'drawdown' => round($dd, 2),
                'daily_pnl' => $entry['daily_pnl'],
            ];
        }

        // Sort by drawdown descending, take top 10 worst days
        $dailyDrawdownList = collect($dailyDrawdownList)
            ->sortByDesc('drawdown')
            ->take(10)
            ->values()
            ->all();

        // R:R ratio (average win / average |loss|)
        $rrRatio = $avgLoss != 0 ? round(abs($avgWin / $avgLoss), 2) : 0;

        // ==============================
        // 2.7 — Account Comparison
        // ==============================
        $accountComparison = TradingAccount::where('user_id', $user->id)
            ->withCount(['tradeHistories as total_trades', 'tradeHistories as wins_count' => fn($q) => $q->where('result', 'win')])
            ->withSum('tradeHistories as total_pnl', 'profit_loss')
            ->orderByDesc('total_pnl')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'broker' => $a->broker,
                'account_number' => $a->account_number,
                'platform' => $a->platform,
                'currency' => $a->currency,
                'total_trades' => $a->total_trades,
                'wins_count' => $a->wins_count,
                'win_rate' => $a->total_trades > 0 ? round(($a->wins_count / $a->total_trades) * 100, 1) : 0,
                'total_pnl' => round($a->total_pnl, 2),
            ]);

        // Accounts for filter
        $accounts = TradingAccount::where('user_id', $user->id)->orderBy('broker')->get();

        return view('analytics.index', compact(
            'totalTrades', 'totalWins', 'totalLosses', 'totalBE', 'winRate',
            'totalPnl', 'avgPnl', 'avgDuration',
            'bestPair', 'worstPair', 'pairStats',
            'pairChartData', 'equityDates', 'equityValues',
            'heatmap', 'dayNames', 'maxTrades',
            'maxWinStreak', 'maxLossStreak', 'currentStreak', 'currentStreakType',
            'avgWin', 'avgLoss', 'profitFactor', 'maxWin', 'maxLoss',
            'maxDrawdown', 'rrRatio', 'dailyDrawdownList',
            'accountComparison',
            'accounts', 'accountId',
        ));
    }
}
