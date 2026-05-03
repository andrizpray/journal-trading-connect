<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ConnectController extends Controller
{
    /**
     * 4.4 — Import journal entries from Jurnal Trading (port 80)
     */
    public function importFromJurnalTrading(Request $request)
    {
        $request->validate([
            'trading_account_id' => 'required|exists:trading_accounts,id',
        ]);

        $account = TradingAccount::where('user_id', Auth::id())
            ->findOrFail($request->trading_account_id);

        // Read from trading_journal database (same server)
        $sourceEntries = DB::connection('mysql_journal')
            ->table('journal_entries')
            ->orderBy('entry_date', 'desc')
            ->get();

        if ($sourceEntries->isEmpty()) {
            return back()->with('error', 'Tidak ada journal di Jurnal Trading untuk diimport.');
        }

        $imported = 0;
        $skipped = 0;

        foreach ($sourceEntries as $source) {
            // Check duplicate by entry_date + currency_pair + profit_loss
            $exists = JournalEntry::where('user_id', Auth::id())
                ->where('currency_pair', $source->currency_pair)
                ->where('profit_loss', $source->profit_loss)
                ->where('created_at', $source->created_at)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Map result enum: breakeven → break_even
            $result = $source->result;
            if ($result === 'breakeven') {
                $result = 'break_even';
            }

            JournalEntry::create([
                'user_id' => Auth::id(),
                'currency_pair' => str_replace('/', '', $source->currency_pair),
                'trade_type' => $source->trade_type === 'analysis' ? 'buy' : $source->trade_type,
                'profit_loss' => $source->profit_loss,
                'result' => $result,
                'analysis' => $source->analysis,
                'lesson_learned' => $source->lesson_learned,
                'emotion_score' => $source->emotion_score,
                'market_condition' => $source->market_condition,
                'template_type' => 'post_trade',
                'tags' => 'imported',
                'created_at' => $source->created_at,
                'updated_at' => $source->updated_at,
            ]);

            $imported++;
        }

        return back()->with('success', sprintf(
            'Import dari Jurnal Trading berhasil! %d jurnal diimport, %d dilewati (sudah ada).',
            $imported, $skipped
        ));
    }

    /**
     * Show connect page with options
     */
    public function index()
    {
        $accounts = TradingAccount::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('broker')
            ->get();

        // Check if jurnal-trading DB is accessible
        $journalConnected = false;
        $journalCount = 0;
        try {
            $journalCount = DB::connection('mysql_journal')
                ->table('journal_entries')
                ->count();
            $journalConnected = true;
        } catch (\Exception $e) {
            // Connection not configured
        }

        return view('connect.index', compact('accounts', 'journalConnected', 'journalCount'));
    }

    /**
     * EA Logger setup page
     */
    public function eaLoggerSetup(Request $request)
    {
        $accounts = TradingAccount::where('user_id', Auth::id())
            ->orderBy('broker')
            ->get();

        $selectedId = $request->get('account_id', $accounts->first()?->id);

        $selectedAccount = $selectedId
            ? TradingAccount::where('user_id', Auth::id())->findOrFail($selectedId)
            : null;

        return view('connect.ea-logger-setup', compact('accounts', 'selectedAccount', 'selectedId'));
    }

    /**
     * Regenerate API token for EA Logger
     */
    public function regenerateToken(Request $request, $id)
    {
        $account = TradingAccount::where('user_id', Auth::id())->findOrFail($id);
        $newToken = $account->regenerateToken();

        return back()->with('success', 'Token berhasil diperbarui.')
            ->with('new_token', $newToken)
            ->with('regenerated_account_id', $id);
    }

    /**
     * Test EA connection (heartbeat test)
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:trading_accounts,id',
        ]);

        $account = TradingAccount::where('user_id', Auth::id())
            ->findOrFail($request->account_id);

        try {
            $response = Http::post(config('app.url') . '/api/ea/heartbeat', [], [
                'Authorization' => 'Bearer ' . $account->api_token,
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Koneksi berhasil! Server merespon: ' . $response->body());
            }

            return back()->with('error', 'Gagal: HTTP ' . $response->status());
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal koneksi ke server: ' . $e->getMessage());
        }
    }
}
