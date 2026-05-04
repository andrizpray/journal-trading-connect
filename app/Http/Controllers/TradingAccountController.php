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
            'currency' => 'required|string|max:10',
            'decimal_places_fx' => 'nullable|integer|min:0|max:8',
            'decimal_places_jpy' => 'nullable|integer|min:0|max:8',
            'decimal_places_metal' => 'nullable|integer|min:0|max:8',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['decimal_places_fx'] = (int) ($validated['decimal_places_fx'] ?? 5);
        $validated['decimal_places_jpy'] = (int) ($validated['decimal_places_jpy'] ?? 3);
        $validated['decimal_places_metal'] = (int) ($validated['decimal_places_metal'] ?? 2);

        $account = TradingAccount::create($validated);

        return redirect()->route('connect.ea-logger', ['account_id' => $account->id])
            ->with('success', 'Akun trading berhasil ditambahkan! Simpan token ini ke pengaturan EA Anda.')
            ->with('new_token', $account->plain_api_token)
            ->with('regenerated_account_id', $account->id);
    }

    public function update(Request $request, $id)
    {
        $account = TradingAccount::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'account_name' => 'nullable|string|max:255',
            'broker' => 'required|string|max:255',
            'platform' => 'required|in:mt4,mt5,other',
            'currency' => 'required|string|max:10',
            'decimal_places_fx' => 'required|integer|min:0|max:8',
            'decimal_places_jpy' => 'required|integer|min:0|max:8',
            'decimal_places_metal' => 'required|integer|min:0|max:8',
        ]);

        $account->update($validated);

        return redirect()->route('trading-accounts.index')
            ->with('success', 'Pengaturan akun berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $account = TradingAccount::where('user_id', Auth::id())->findOrFail($id);
        $account->delete();

        return redirect()->route('trading-accounts.index')
            ->with('success', 'Akun trading berhasil dihapus!');
    }
}
