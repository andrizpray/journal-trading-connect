<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradingAccountController extends Controller
{
    public function index()
    {
        $accounts = TradingAccount::where('user_id', Auth::id())
            ->withCount('tradeHistories')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('trading-accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_number' => 'required|string|max:20',
            'account_name' => 'nullable|string|max:255',
            'broker' => 'required|string|max:255',
            'platform' => 'required|in:mt4,mt5,other',
        ]);

        $validated['user_id'] = Auth::id();

        TradingAccount::create($validated);

        return redirect()->route('trading-accounts.index')
            ->with('success', 'Akun trading berhasil ditambahkan!');
    }

    public function destroy($id)
    {
        $account = TradingAccount::where('user_id', Auth::id())->findOrFail($id);
        $account->delete();

        return redirect()->route('trading-accounts.index')
            ->with('success', 'Akun trading berhasil dihapus!');
    }
}
