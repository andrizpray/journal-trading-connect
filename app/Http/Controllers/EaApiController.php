<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EaApiController extends Controller
{
    /**
     * Simpan satu trade dari EA.
     * POST /api/ea/trade
     */
    public function storeTrade(Request $request)
    {
        $account = $request->attributes->get('ea_account');

        $validated = $request->validate([
            'ticket'            => 'required|string|max:32',
            'open_date'         => 'required|date',
            'close_date'        => 'nullable|date',
            'currency_pair'     => 'required|string|max:20',
            'trade_type'        => 'required|in:buy,sell',
            'lot_size'          => 'required|numeric|min:0',
            'open_price'        => 'required|numeric|min:0',
            'close_price'       => 'nullable|numeric|min:0',
            'stop_loss'         => 'nullable|numeric',
            'take_profit'       => 'nullable|numeric',
            'swap'              => 'nullable|numeric',
            'commission'        => 'nullable|numeric',
            'profit_loss'       => 'required|numeric',
            'duration_minutes'  => 'nullable|integer',
            'comment'           => 'nullable|string|max:255',
        ]);

        // Cek duplicate berdasarkan ticket + account
        $exists = TradeHistory::where('ticket', $validated['ticket'])
            ->where('trading_account_id', $account->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'   => 'ok',
                'message'  => 'Trade already exists',
                'trade_id' => null,
                'duplicate' => true,
            ]);
        }

        // Hitung result
        $result = $this->calculateResult($validated['profit_loss']);

        // Hitung durasi otomatis kalau tidak dikirim
        $duration = $validated['duration_minutes'] ?? null;
        if ($duration === null && !empty($validated['close_date'])) {
            $openDate = Carbon::parse($validated['open_date']);
            $closeDate = Carbon::parse($validated['close_date']);
            $duration = $openDate->diffInMinutes($closeDate);
        }

        $trade = TradeHistory::create([
            'user_id'            => $account->user_id,
            'trading_account_id' => $account->id,
            'ticket'             => $validated['ticket'],
            'open_date'          => $validated['open_date'],
            'close_date'         => $validated['close_date'] ?? null,
            'currency_pair'      => strtoupper($validated['currency_pair']),
            'trade_type'         => strtolower($validated['trade_type']),
            'lot_size'           => $validated['lot_size'],
            'open_price'         => $validated['open_price'],
            'close_price'        => $validated['close_price'] ?? null,
            'stop_loss'          => $validated['stop_loss'] ?? null,
            'take_profit'        => $validated['take_profit'] ?? null,
            'swap'               => $validated['swap'] ?? 0,
            'commission'         => $validated['commission'] ?? 0,
            'profit_loss'        => $validated['profit_loss'],
            'result'             => $result,
            'duration_minutes'   => $duration,
            'comment'            => $validated['comment'] ?? '',
            'imported_at'        => now(),
        ]);

        // Update last_synced_at
        $account->update([
            'last_synced_at' => now(),
            'total_trades'   => TradeHistory::where('trading_account_id', $account->id)->count(),
            'total_pnl'      => TradeHistory::where('trading_account_id', $account->id)->sum('profit_loss'),
        ]);

        return response()->json([
            'status'    => 'ok',
            'message'   => 'Trade saved',
            'trade_id'  => $trade->id,
            'duplicate' => false,
        ]);
    }

    /**
     * Simpan batch trades dari EA (max 50).
     * POST /api/ea/trade/batch
     */
    public function storeBatch(Request $request)
    {
        $account = $request->attributes->get('ea_account');

        $validated = $request->validate([
            'trades' => 'required|array|max:50',
            'trades.*.ticket'           => 'required|string|max:32',
            'trades.*.open_date'        => 'required|date',
            'trades.*.close_date'       => 'nullable|date',
            'trades.*.currency_pair'    => 'required|string|max:20',
            'trades.*.trade_type'       => 'required|in:buy,sell',
            'trades.*.lot_size'         => 'required|numeric|min:0',
            'trades.*.open_price'       => 'required|numeric|min:0',
            'trades.*.close_price'      => 'nullable|numeric|min:0',
            'trades.*.stop_loss'        => 'nullable|numeric',
            'trades.*.take_profit'      => 'nullable|numeric',
            'trades.*.swap'             => 'nullable|numeric',
            'trades.*.commission'       => 'nullable|numeric',
            'trades.*.profit_loss'      => 'required|numeric',
            'trades.*.duration_minutes' => 'nullable|integer',
            'trades.*.comment'          => 'nullable|string|max:255',
        ]);

        $saved = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($validated['trades'] as $tradeData) {
            try {
                // Cek duplicate
                $exists = TradeHistory::where('ticket', $tradeData['ticket'])
                    ->where('trading_account_id', $account->id)
                    ->exists();

                if ($exists) {
                    $duplicates++;
                    continue;
                }

                $result = $this->calculateResult($tradeData['profit_loss']);

                $duration = $tradeData['duration_minutes'] ?? null;
                if ($duration === null && !empty($tradeData['close_date'])) {
                    $openDate = Carbon::parse($tradeData['open_date']);
                    $closeDate = Carbon::parse($tradeData['close_date']);
                    $duration = $openDate->diffInMinutes($closeDate);
                }

                TradeHistory::create([
                    'user_id'            => $account->user_id,
                    'trading_account_id' => $account->id,
                    'ticket'             => $tradeData['ticket'],
                    'open_date'          => $tradeData['open_date'],
                    'close_date'         => $tradeData['close_date'] ?? null,
                    'currency_pair'      => strtoupper($tradeData['currency_pair']),
                    'trade_type'         => strtolower($tradeData['trade_type']),
                    'lot_size'           => $tradeData['lot_size'],
                    'open_price'         => $tradeData['open_price'],
                    'close_price'        => $tradeData['close_price'] ?? null,
                    'stop_loss'          => $tradeData['stop_loss'] ?? null,
                    'take_profit'        => $tradeData['take_profit'] ?? null,
                    'swap'               => $tradeData['swap'] ?? 0,
                    'commission'         => $tradeData['commission'] ?? 0,
                    'profit_loss'        => $tradeData['profit_loss'],
                    'result'             => $result,
                    'duration_minutes'   => $duration,
                    'comment'            => $tradeData['comment'] ?? '',
                    'imported_at'        => now(),
                ]);

                $saved++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        // Update last_synced_at
        $account->update([
            'last_synced_at' => now(),
            'total_trades'   => TradeHistory::where('trading_account_id', $account->id)->count(),
            'total_pnl'      => TradeHistory::where('trading_account_id', $account->id)->sum('profit_loss'),
        ]);

        return response()->json([
            'status'     => 'ok',
            'message'    => sprintf('Processed %d trades', count($validated['trades'])),
            'saved'      => $saved,
            'duplicates' => $duplicates,
            'errors'     => $errors,
        ]);
    }

    /**
     * Heartbeat — EA kirim sinyal masih hidup.
     * POST /api/ea/heartbeat
     */
    public function heartbeat(Request $request)
    {
        $account = $request->attributes->get('ea_account');

        $account->update([
            'last_synced_at' => now(),
        ]);

        $pendingTrades = TradeHistory::where('trading_account_id', $account->id)->count();

        return response()->json([
            'status'        => 'ok',
            'server_time'   => now()->toDateTimeString(),
            'pending_trades'=> $pendingTrades,
            'message'       => 'Heartbeat received',
        ]);
    }

    /**
     * EA minta konfigurasi awal.
     * GET /api/ea/config
     */
    public function getConfig(Request $request)
    {
        $account = $request->attributes->get('ea_account');

        $serverUrl = config('app.url');

        return response()->json([
            'status'               => 'ok',
            'account_id'           => $account->id,
            'account_number'       => $account->account_number,
            'broker'               => $account->broker,
            'platform'             => $account->platform,
            'server_url'           => $serverUrl,
            'send_interval_seconds'=> 30,
            'max_trades_per_request'=> 50,
        ]);
    }

    /**
     * Hitung result dari profit_loss.
     */
    private function calculateResult(float $profitLoss): string
    {
        if ($profitLoss > 0) return 'win';
        if ($profitLoss < 0) return 'loss';
        return 'break_even';
    }
}
