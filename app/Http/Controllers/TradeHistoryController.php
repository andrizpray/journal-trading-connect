<?php

namespace App\Http\Controllers;

use App\Models\TradeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradeHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = TradeHistory::where('user_id', Auth::id())
            ->with('tradingAccount');

        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('trading_account_id', $request->account_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('close_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('close_date', '<=', $request->date_to);
        }

        // Filter by currency pair
        if ($request->filled('pair')) {
            $query->where('currency_pair', $request->pair);
        }

        // Filter by result
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        $trades = $query->orderBy('close_date', 'desc')->paginate(50);

        // Get unique pairs for filter dropdown
        $pairs = TradeHistory::where('user_id', Auth::id())
            ->distinct()
            ->pluck('currency_pair')
            ->sort()
            ->values();

        // Get user accounts for filter
        $accounts = Auth::user()->tradingAccounts()->orderBy('broker')->get();

        return view('trade-history.index', compact('trades', 'pairs', 'accounts'));
    }
}
