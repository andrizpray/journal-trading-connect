<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\TradeHistory;
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
     * Test EA connection (heartbeat test) — legacy full-page reload
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:trading_accounts,id',
        ]);

        $account = TradingAccount::where('user_id', Auth::id())
            ->findOrFail($request->account_id);

        $result = $this->performConnectionTest($account);

        return back()->with($result['type'], $result['message']);
    }

    /**
     * Test EA connection via AJAX — returns JSON
     */
    public function testConnectionAjax(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:trading_accounts,id',
        ]);

        $account = TradingAccount::where('user_id', Auth::id())
            ->findOrFail($request->account_id);

        $result = $this->performConnectionTest($account);

        return response()->json($result);
    }

    /**
     * Check EA connection status:
     * - Apakah server API bisa dijangkau?
     * - Apakah token valid?
     * - Apakah EA Logger aktif mengirim data dari MT4/MT5?
     */
    private function performConnectionTest(TradingAccount $account): array
    {
        $serverUrl = config('app.url');

        // Step 1: Cek apakah server API bisa dijangkau & token valid
        $apiOk = false;
        $apiMessage = '';
        $apiHint = '';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->api_token,
                'Accept' => 'application/json',
            ])->withoutVerifying()
              ->timeout(10)
              ->post($serverUrl . '/api/ea/heartbeat');

            if ($response->successful()) {
                $apiOk = true;
            } else {
                $status = $response->status();
                if ($status === 401) {
                    $apiMessage = 'Token tidak valid';
                    $apiHint = 'Generate token baru, lalu update parameter EA di MT4/MT5.';
                } else {
                    $apiMessage = "Server merespon HTTP {$status}";
                    $apiHint = 'Coba lagi dalam beberapa saat.';
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $apiMessage = 'Server tidak bisa dijangkau';
            $apiHint = 'Pastikan server berjalan. URL: ' . $serverUrl;
        } catch (\Exception $e) {
            $apiMessage = 'Gagal menghubungi server';
            $apiHint = 'Cek koneksi internet dan coba lagi.';
        }

        // Step 2: Cek apakah EA aktif mengirim data
        $eaActive = false;
        $eaStatus = 'never'; // never | inactive | active
        $lastSync = $account->last_synced_at;
        $totalTrades = TradeHistory::where('trading_account_id', $account->id)->count();

        if ($lastSync) {
            $minutesAgo = $lastSync->diffInMinutes(now());
            if ($minutesAgo <= 10) {
                $eaActive = true;
                $eaStatus = 'active';
            } else {
                $eaStatus = 'inactive';
            }
        }

        // Build result
        if ($apiOk && $eaActive) {
            return [
                'status' => 'connected',
                'title' => 'EA Logger Terhubung & Aktif',
                'message' => 'Akun trading sudah terhubung ke server. EA Logger sedang mengirim data trade dari MT4/MT5.',
                'details' => [
                    'account' => $account->broker . ' (' . $account->account_number . ')',
                    'last_sync' => $lastSync?->format('d M Y, H:i'),
                    'last_sync_ago' => $lastSync?->diffForHumans(),
                    'total_trades' => $totalTrades,
                    'server_url' => $serverUrl,
                ],
            ];
        }

        if ($apiOk && $eaStatus === 'inactive') {
            return [
                'status' => 'disconnected',
                'title' => 'Server OK, tapi EA Tidak Aktif',
                'message' => 'Server bisa dijangkau dan token valid, tapi EA Logger belum mengirim data baru.',
                'details' => [
                    'account' => $account->broker . ' (' . $account->account_number . ')',
                    'last_sync' => $lastSync?->format('d M Y, H:i'),
                    'last_sync_ago' => $lastSync?->diffForHumans(),
                    'total_trades' => $totalTrades,
                ],
                'hint' => 'Pastikan EA Logger masih ter-attach di chart MT4/MT5 dan terminal sedang berjalan.',
            ];
        }

        if ($apiOk && $eaStatus === 'never') {
            return [
                'status' => 'not_setup',
                'title' => 'Server OK, EA Belum Pernah Terhubung',
                'message' => 'Server bisa dijangkau, tapi EA Logger belum pernah mengirim data dari akun ini.',
                'details' => [
                    'account' => $account->broker . ' (' . $account->account_number . ')',
                ],
                'steps' => [
                    'Download EA Logger (langkah 3)',
                    'Copy file ke folder MQL4/Indicators atau MQL5/Indicators',
                    'Compile di MetaEditor (F7)',
                    'Attach ke chart, paste API Token di parameter',
                    'Tambahkan URL ke Allow WebRequest di Options',
                ],
            ];
        }

        // API not reachable
        return [
            'status' => 'server_error',
            'title' => 'Tidak Bisa Terhubung ke Server',
            'message' => $apiMessage,
            'hint' => $apiHint,
        ];
    }
}
