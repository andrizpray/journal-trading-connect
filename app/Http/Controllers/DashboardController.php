<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use App\Models\TradeHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalAccounts = TradingAccount::where('user_id', $user->id)->count();
        $totalTrades = TradeHistory::where('user_id', $user->id)->count();
        $totalPnl = TradeHistory::where('user_id', $user->id)->sum('profit_loss');
        $totalWins = TradeHistory::where('user_id', $user->id)->where('result', 'win')->count();
        $winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;

        // Recent trades (last 10)
        $recentTrades = TradeHistory::where('user_id', $user->id)
            ->with('tradingAccount')
            ->orderBy('close_date', 'desc')
            ->take(10)
            ->get();

        // Daily P&L for last 30 days
        $chartData = TradeHistory::where('user_id', $user->id)
            ->where('close_date', '>=', now()->subDays(30))
            ->selectRaw('DATE(close_date) as date, SUM(profit_loss) as daily_pnl')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates with 0
        $dates = [];
        $pnlValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('d M');
            $found = $chartData->firstWhere('date', $date);
            $pnlValues[] = $found ? (float) $found->daily_pnl : 0;
        }

        return view('dashboard.index', compact(
            'totalAccounts', 'totalTrades', 'totalPnl', 'winRate',
            'recentTrades', 'dates', 'pnlValues'
        ));
    }
}
