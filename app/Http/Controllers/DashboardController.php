<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $accountId = $request->filled('account') ? (int) $request->account : null;

        // Build base query with optional account filter
        $tradeQuery = TradeHistory::where('user_id', $user->id);
        if ($accountId) {
            $tradeQuery->where('trading_account_id', $accountId);
        }

        $totalAccounts = TradingAccount::where('user_id', $user->id)->count();
        $totalTrades = (clone $tradeQuery)->count();
        $totalPnl = (clone $tradeQuery)->sum('profit_loss');
        $totalWins = (clone $tradeQuery)->where('result', 'win')->count();
        $winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;

        // Recent trades (last 10)
        $recentTrades = (clone $tradeQuery)
            ->with('tradingAccount')
            ->orderBy('close_date', 'desc')
            ->take(10)
            ->get();

        // Daily P&L for last 30 days
        $chartData = (clone $tradeQuery)
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

        // User accounts for filter
        $accounts = TradingAccount::where('user_id', $user->id)->orderBy('broker')->get();

        return view('dashboard.index', compact(
            'totalAccounts', 'totalTrades', 'totalPnl', 'winRate',
            'recentTrades', 'dates', 'pnlValues', 'accounts', 'accountId'
        ));
    }
}
