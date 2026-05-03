<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use App\Models\TradeHistory;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function index()
    {
        $accounts = TradingAccount::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('broker')
            ->get();

        return view('import.index', compact('accounts'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'trading_account_id' => 'required|exists:trading_accounts,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'auto_journal' => 'nullable',
        ]);

        $account = TradingAccount::where('user_id', Auth::id())
            ->findOrFail($request->trading_account_id);

        $file = $request->file('csv_file');
        $autoJournal = $request->has('auto_journal');

        $path = $file->getRealPath();
        $rows = array_map('str_getcsv', file($path));

        if (count($rows) < 2) {
            return back()->with('error', 'File CSV kosong atau tidak valid.');
        }

        // Parse header - detect column positions
        $header = array_map('trim', array_map('strtolower', $rows[0]));
        $colMap = $this->mapColumns($header);

        if (!$colMap) {
            return back()->with('error', 'Format CSV tidak dikenali. Pastikan kolom: Ticket, Open Date, Close Date, Type, Lot, Symbol, Open Price, Close Price, SL, TP, Swap, Commission, Profit, Comment.');
        }

        $imported = 0;
        $wins = 0;
        $losses = 0;
        $breakEvens = 0;
        $skipped = 0;
        $errors = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (count($row) < 5) continue;

            $ticket = trim($row[$colMap['ticket']] ?? '');

            // Skip empty rows
            if (empty($ticket)) continue;

            // Skip already imported
            $exists = TradeHistory::where('user_id', Auth::id())
                ->where('trading_account_id', $account->id)
                ->where('ticket', $ticket)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                $openDate = $this->parseDate($row[$colMap['open_date']] ?? '');
                $closeDate = $this->parseDate($row[$colMap['close_date']] ?? '');
                $profitLoss = (float) str_replace([',', ' '], '', $row[$colMap['profit']] ?? 0);

                $result = 'break_even';
                if ($profitLoss > 0) {
                    $result = 'win';
                    $wins++;
                } elseif ($profitLoss < 0) {
                    $result = 'loss';
                    $losses++;
                } else {
                    $breakEvens++;
                }

                // Calculate duration
                $duration = null;
                if ($openDate && $closeDate) {
                    $duration = $openDate->diffInMinutes($closeDate);
                }

                // Normalize trade type
                $rawType = strtolower(trim($row[$colMap['type']] ?? 'buy'));
                $tradeType = $this->normalizeTradeType($rawType);

                $trade = TradeHistory::create([
                    'user_id' => Auth::id(),
                    'trading_account_id' => $account->id,
                    'ticket' => $ticket,
                    'open_date' => $openDate,
                    'close_date' => $closeDate,
                    'currency_pair' => trim($row[$colMap['symbol']] ?? ''),
                    'trade_type' => $tradeType,
                    'lot_size' => (float) str_replace(',', '', $row[$colMap['lot']] ?? 0),
                    'open_price' => $this->parseFloat($row[$colMap['open_price']] ?? null),
                    'close_price' => $this->parseFloat($row[$colMap['close_price']] ?? null),
                    'stop_loss' => $this->parseFloat($row[$colMap['sl']] ?? null),
                    'take_profit' => $this->parseFloat($row[$colMap['tp']] ?? null),
                    'swap' => (float) str_replace(',', '', $row[$colMap['swap']] ?? 0),
                    'commission' => (float) str_replace(',', '', $row[$colMap['commission']] ?? 0),
                    'profit_loss' => $profitLoss,
                    'result' => $result,
                    'duration_minutes' => $duration,
                    'comment' => trim($row[$colMap['comment']] ?? ''),
                    'imported_at' => now(),
                ]);

                // Auto-create journal entry
                if ($autoJournal) {
                    JournalEntry::create([
                        'user_id' => Auth::id(),
                        'trade_history_id' => $trade->id,
                        'currency_pair' => $trade->currency_pair,
                        'trade_type' => in_array($tradeType, ['buy_limit', 'buy_stop']) ? 'buy' : (in_array($tradeType, ['sell_limit', 'sell_stop']) ? 'sell' : $tradeType),
                        'profit_loss' => $profitLoss,
                        'result' => $result,
                        'auto_imported' => true,
                    ]);
                }

                $imported++;
            } catch (\e $e) {
                $errors[] = "Baris " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        // Update account stats
        $account->update([
            'last_synced_at' => now(),
            'total_trades' => TradeHistory::where('trading_account_id', $account->id)->count(),
            'total_pnl' => TradeHistory::where('trading_account_id', $account->id)->sum('profit_loss'),
        ]);

        return back()->with('success', sprintf(
            'Import berhasil! %d trade diimport (%d win, %d loss, %d break even), %d dilewati (sudah ada).',
            $imported, $wins, $losses, $breakEvens, $skipped
        ));
    }

    public function template()
    {
        $filename = 'trade_history_template.csv';
        $headers = ['Ticket', 'Open Date', 'Close Date', 'Type', 'Lot', 'Symbol', 'Open Price', 'Close Price', 'SL', 'TP', 'Swap', 'Commission', 'Profit', 'Comment'];
        $sample = ['123456', '2026-01-15 08:30:00', '2026-01-15 10:45:00', 'buy', '0.10', 'EURUSD', '1.08500', '1.08750', '1.08300', '1.09000', '-1.25', '0.00', '25.00', 'Sample trade'];

        $content = implode(',', $headers) . "\n" . implode(',', $sample);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function mapColumns(array $header): ?array
    {
        $mapping = [
            'ticket' => ['ticket', 'order', 'order ticket', 'order #'],
            'open_date' => ['open date', 'opentime', 'open time', 'open_date', 'datetime'],
            'close_date' => ['close date', 'closetime', 'close time', 'close_date', 'time'],
            'type' => ['type', 'trade type', 'direction', 'side'],
            'lot' => ['lot', 'lots', 'lotsize', 'lot size', 'volume'],
            'symbol' => ['symbol', 'pair', 'currency pair', 'instrument', 'currency_pair'],
            'open_price' => ['open price', 'openprice', 'open', 'price open'],
            'close_price' => ['close price', 'closeprice', 'close', 'price close'],
            'sl' => ['sl', 'stop loss', 's/l', 'stoploss'],
            'tp' => ['tp', 'take profit', 't/p', 'takeprofit'],
            'swap' => ['swap', 'swaps', 'rollover'],
            'commission' => ['commission', 'commissions', 'comm'],
            'profit' => ['profit', 'p&l', 'pnl', 'pl', 'result', 'profit/loss', 'net profit'],
            'comment' => ['comment', 'comments', 'notes', 'remarks'],
        ];

        $result = [];
        foreach ($mapping as $field => $aliases) {
            $found = false;
            foreach ($aliases as $alias) {
                $index = array_search($alias, $header);
                if ($index !== false) {
                    $result[$field] = $index;
                    $found = true;
                    break;
                }
            }
            // Required fields
            if (!$found && in_array($field, ['ticket', 'symbol', 'profit'])) {
                return null;
            }
        }

        return $result;
    }

    private function parseDate(string $value): ?\Carbon\Carbon
    {
        if (empty($value)) return null;

        $formats = [
            'Y-m-d H:i:s',
            'Y.m.d H:i:s',
            'd.m.Y H:i:s',
            'd/m/Y H:i:s',
            'Y-m-d',
            'd.m.Y',
            'd/m/Y',
            'm/d/Y H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, trim($value));
            } catch (\e $e) {
                continue;
            }
        }

        try {
            return new \Carbon\Carbon($value);
        } catch (\e $e) {
            return null;
        }
    }

    private function parseFloat(?string $value): ?float
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') return null;
        return (float) str_replace([',', ' '], '', $value);
    }

    private function normalizeTradeType(string $type): string
    {
        $type = str_replace(' ', '_', $type);
        $map = [
            'buy' => 'buy',
            'sell' => 'sell',
            'buy_limit' => 'buy_limit',
            'sell_limit' => 'sell_limit',
            'buy_stop' => 'buy_stop',
            'sell_stop' => 'sell_stop',
            'balik' => 'buy', // some brokers
        ];

        return $map[strtolower($type)] ?? 'buy';
    }
}
