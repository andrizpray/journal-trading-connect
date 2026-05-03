<?php

namespace App\Http\Controllers;

use App\Models\TradeHistory;
use App\Exports\TradeHistoryExport;
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

    public function export(Request $request)
    {
        $export = new TradeHistoryExport(
            accountId: $request->filled('account_id') ? (int) $request->account_id : null,
            pair: $request->filled('pair') ? $request->pair : null,
            result: $request->filled('result') ? $request->result : null,
            dateFrom: $request->filled('date_from') ? $request->date_from : null,
            dateTo: $request->filled('date_to') ? $request->date_to : null,
        );

        $filename = 'trade-history-' . now()->format('Y-m-d') . '.xlsx';

        return $export->download($filename);
    }

    public function edit($id)
    {
        $trade = TradeHistory::where('user_id', Auth::id())->findOrFail($id);
        $accounts = Auth::user()->tradingAccounts()->orderBy('broker')->get();

        return view('trade-history.edit', compact('trade', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $trade = TradeHistory::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'currency_pair' => 'required|string|max:20',
            'trade_type' => 'required|in:buy,sell,buy_limit,sell_limit,buy_stop,sell_stop',
            'lot_size' => 'required|numeric|min:0.01',
            'open_price' => 'nullable|numeric',
            'close_price' => 'nullable|numeric',
            'stop_loss' => 'nullable|numeric',
            'take_profit' => 'nullable|numeric',
            'swap' => 'nullable|numeric',
            'commission' => 'nullable|numeric',
            'profit_loss' => 'required|numeric',
            'comment' => 'nullable|string|max:500',
        ]);

        // Auto-determine result from P&L
        $validated['result'] = $validated['profit_loss'] > 0 ? 'win'
            : ($validated['profit_loss'] < 0 ? 'loss' : 'break_even');

        $trade->update($validated);

        return redirect()->route('trade-history.index')
            ->with('success', 'Trade berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $trade = TradeHistory::where('user_id', Auth::id())->findOrFail($id);
        $trade->delete();

        return redirect()->route('trade-history.index')
            ->with('success', 'Trade berhasil dihapus.');
    }
}
