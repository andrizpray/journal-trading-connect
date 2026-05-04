<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->filled('period') ? $request->period : 'all_time';
        $sortBy = $request->filled('sort') ? $request->sort : 'win_rate';

        // Date range based on period
        $dateFilter = null;
        switch ($period) {
            case 'weekly':
                $dateFilter = now()->startOfWeek();
                break;
            case 'monthly':
                $dateFilter = now()->startOfMonth();
                break;
            case 'quarterly':
                $dateFilter = now()->subMonths(3);
                break;
            case 'yearly':
                $dateFilter = now()->startOfYear();
                break;
            default:
                $dateFilter = null;
        }

        // Base query: only users who opted in
        $query = User::where('leaderboard_opt_in', true)
            ->whereHas('tradeHistories', function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
            });

        // Build leaderboard data with subquery
        $leaderboard = $query->withCount([
            'tradeHistories as total_trades' => function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
            },
            'tradeHistories as wins_count' => function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
                $q->where('result', 'win');
            },
            'tradeHistories as loss_count' => function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
                $q->where('result', 'loss');
            },
        ])
        ->withSum([
            'tradeHistories as total_pnl' => function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
            },
            'tradeHistories as wins_pnl' => function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
                $q->where('result', 'win');
            },
            'tradeHistories as losses_pnl' => function ($q) use ($dateFilter) {
                if ($dateFilter) {
                    $q->where('close_date', '>=', $dateFilter);
                }
                $q->where('result', 'loss');
            },
        ], 'profit_loss')
        ->get()
        ->map(function ($user) {
            $winRate = $user->total_trades > 0
                ? round(($user->wins_count / $user->total_trades) * 100, 1)
                : 0;
            $avgPnl = $user->total_trades > 0
                ? round($user->total_pnl / $user->total_trades, 2)
                : 0;
            $totalWinPnl = (float) ($user->wins_pnl ?? 0);
            $totalLossPnl = abs((float) ($user->losses_pnl ?? 0));
            $profitFactor = $totalLossPnl > 0
                ? round($totalWinPnl / $totalLossPnl, 2)
                : ($totalWinPnl > 0 ? 99.99 : 0);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'total_trades' => $user->total_trades,
                'wins_count' => $user->wins_count,
                'loss_count' => $user->loss_count,
                'win_rate' => $winRate,
                'total_pnl' => round((float) $user->total_pnl, 2),
                'avg_pnl' => $avgPnl,
                'profit_factor' => $profitFactor,
            ];
        });

        // Sort
        switch ($sortBy) {
            case 'win_rate':
                $leaderboard = $leaderboard->sortByDesc('win_rate');
                break;
            case 'total_pnl':
                $leaderboard = $leaderboard->sortByDesc('total_pnl');
                break;
            case 'total_trades':
                $leaderboard = $leaderboard->sortByDesc('total_trades');
                break;
            case 'avg_pnl':
                $leaderboard = $leaderboard->sortByDesc('avg_pnl');
                break;
            default:
                $leaderboard = $leaderboard->sortByDesc('win_rate');
        }

        // Add rank
        $leaderboard = $leaderboard->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        // Find current user's rank (if opted in)
        $myRank = null;
        $myStats = null;
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->leaderboard_opt_in) {
            $myRankEntry = $leaderboard->firstWhere('id', $currentUser->id);
            if ($myRankEntry) {
                $myRank = $myRankEntry['rank'];
                $myStats = $myRankEntry;
            }
        }

        return view('leaderboard.index', compact(
            'leaderboard', 'period', 'sortBy', 'myRank', 'myStats'
        ));
    }

    public function toggleOptIn(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'leaderboard_opt_in' => !$user->leaderboard_opt_in,
        ]);

        $status = $user->fresh()->leaderboard_opt_in;

        return back()->with('success', $status
            ? 'Berhasil bergabung ke leaderboard!'
            : 'Berhasil keluar dari leaderboard.');
    }
}
