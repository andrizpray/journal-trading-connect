<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\TradingAccount;
use App\Models\TradeHistory;
use App\Models\JournalEntry;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User
        $user = User::firstOrCreate(
            ['email' => 'andriz@demo.com'],
            [
                'name' => 'Andriz',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Trading Accounts
        $accounts = [
            [
                'account_number' => '12345678',
                'account_name' => 'Akun Utama',
                'broker' => 'OctaFX',
                'platform' => 'mt5',
            ],
            [
                'account_number' => '87654321',
                'account_name' => 'Akun Scalping',
                'broker' => 'XM',
                'platform' => 'mt5',
            ],
            [
                'account_number' => '11223344',
                'account_name' => null,
                'broker' => 'ICMarkets',
                'platform' => 'mt4',
            ],
        ];

        foreach ($accounts as $accData) {
            TradingAccount::firstOrCreate(
                ['user_id' => $user->id, 'account_number' => $accData['account_number']],
                $accData + ['user_id' => $user->id, 'sync_method' => 'csv', 'is_active' => true]
            );
        }

        $accountList = TradingAccount::where('user_id', $user->id)->get();

        // 3. Trade Histories
        $pairs = ['EURUSD', 'GBPUSD', 'USDJPY', 'XAUUSD', 'GBPJPY', 'AUDUSD', 'USDCAD', 'NZDUSD'];
        $types = ['buy', 'sell', 'buy_limit', 'sell_stop'];
        $marketConditions = ['trending', 'ranging', 'volatile', 'calm'];
        $strategies = ['Breakout + RSI', 'Scalping EMA', 'Swing Trading', 'News Trading', 'Price Action', 'Fibonacci Retracement'];

        $trades = [];
        for ($i = 0; $i < 85; $i++) {
            $daysAgo = rand(1, 60);
            $openDate = now()->subDays($daysAgo)->setHour(rand(6, 20))->setMinute(rand(0, 59));
            $durationMinutes = rand(5, 1440); // 5 min - 24 hours
            $closeDate = (clone $openDate)->addMinutes($durationMinutes);

            $pair = $pairs[array_rand($pairs)];
            $type = $types[array_rand($types)];
            $lot = [0.01, 0.02, 0.05, 0.10, 0.20, 0.50][array_rand([0, 1, 2, 3, 4, 5])];

            // Random price based on pair
            $basePrice = match ($pair) {
                'EURUSD' => 1.08500,
                'GBPUSD' => 1.26500,
                'USDJPY' => 149.500,
                'XAUUSD' => 2340.00,
                'GBPJPY' => 189.200,
                'AUDUSD' => 0.65500,
                'USDCAD' => 1.36500,
                'NZDUSD' => 0.61000,
                default => 1.00000,
            };

            $pipMultiplier = in_array($pair, ['USDJPY', 'GBPJPY', 'XAUUSD']) ? 0.01 : 0.0001;
            $pipMove = rand(-80, 80);
            $openPrice = $basePrice + ($pipMove * $pipMultiplier * rand(1, 5));
            $closePrice = $openPrice + (rand(-50, 50) * $pipMultiplier);

            // Profit based on lot and pips moved
            $pipsMoved = ($closePrice - $openPrice) / $pipMultiplier;
            $isBuy = in_array($type, ['buy', 'buy_limit', 'buy_stop']);
            $profitPips = $isBuy ? $pipsMoved : -$pipsMoved;
            $pipValue = match ($pair) {
                'XAUUSD' => $lot * 1.0,
                'USDJPY', 'GBPJPY' => $lot * 0.67,
                default => $lot * 10.0,
            };
            $profitLoss = round($profitPips * $pipValue + rand(-5, 5), 2);
            $swap = round(rand(-30, 10) * 0.01 * $lot, 2);
            $commission = round($lot * 7.0, 2);

            $result = $profitLoss > 0 ? 'win' : ($profitLoss < 0 ? 'loss' : 'break_even');

            $trades[] = [
                'user_id' => $user->id,
                'trading_account_id' => $accountList[random_int(0, $accountList->count() - 1)]->id,
                'ticket' => 1000000 + $i,
                'open_date' => $openDate->format('Y-m-d'),
                'close_date' => $closeDate->format('Y-m-d'),
                'currency_pair' => $pair,
                'trade_type' => in_array($type, ['buy_limit']) ? 'buy_limit' : (in_array($type, ['sell_stop']) ? 'sell_stop' : $type),
                'lot_size' => $lot,
                'open_price' => $openPrice,
                'close_price' => $closePrice,
                'stop_loss' => $isBuy ? round($openPrice - rand(10, 30) * $pipMultiplier, 5) : round($openPrice + rand(10, 30) * $pipMultiplier, 5),
                'take_profit' => $isBuy ? round($openPrice + rand(10, 30) * $pipMultiplier, 5) : round($openPrice - rand(10, 30) * $pipMultiplier, 5),
                'swap' => $swap,
                'commission' => $commission,
                'profit_loss' => $profitLoss,
                'result' => $result,
                'duration_minutes' => $durationMinutes,
                'comment' => rand(1, 5) === 1 ? 'Manual close' : '',
                'imported_at' => now(),
                'created_at' => $closeDate,
                'updated_at' => $closeDate,
            ];
        }

        // Insert in batches
        foreach (array_chunk($trades, 20) as $batch) {
            TradeHistory::insert($batch);
        }

        // 4. Update account stats
        foreach ($accountList as $account) {
            $account->update([
                'total_trades' => TradeHistory::where('trading_account_id', $account->id)->count(),
                'total_pnl' => TradeHistory::where('trading_account_id', $account->id)->sum('profit_loss'),
                'last_synced_at' => now()->subHours(rand(1, 48)),
            ]);
        }

        // 5. Journal Entries (from some trades + manual)
        $recentTrades = TradeHistory::where('user_id', $user->id)
            ->orderBy('close_date', 'desc')
            ->take(30)
            ->get();

        foreach ($recentTrades as $idx => $trade) {
            if ($idx % 2 !== 0) continue; // Journal for half the trades

            JournalEntry::create([
                'user_id' => $user->id,
                'trade_history_id' => $trade->id,
                'currency_pair' => $trade->currency_pair,
                'trade_type' => in_array($trade->trade_type, ['buy_limit']) ? 'buy' : (in_array($trade->trade_type, ['sell_stop']) ? 'sell' : $trade->trade_type),
                'profit_loss' => $trade->profit_loss,
                'result' => $trade->result,
                'strategy_used' => $strategies[array_rand($strategies)],
                'market_condition' => $marketConditions[array_rand($marketConditions)],
                'emotion_score' => rand(1, 5),
                'analysis' => $trade->result === 'win'
                    ? 'Entry tepat di area support/resistance key. Momentum konfirmasi dari RSI divergen positif. Risk reward ratio terjaga.'
                    : 'Overtrading di zona ranging, seharusnya wait for breakout. SL terlalu ketat akibat volatilitas tinggi.',
                'lesson_learned' => $trade->result === 'win'
                    ? 'Sabar menunggu konfirmasi adalah kunci. Jangan FOMO masuk sebelum setup lengkap.'
                    : 'Perlu disiplin dengan trading plan. Hindari entry tanpa konfirmasi multi-timeframe.',
                'auto_imported' => true,
            ]);
        }

        // 5 manual journal entries (no trade link)
        for ($i = 0; $i < 5; $i++) {
            JournalEntry::create([
                'user_id' => $user->id,
                'trade_history_id' => null,
                'currency_pair' => $pairs[array_rand($pairs)],
                'trade_type' => ['buy', 'sell'][array_rand([0, 1])],
                'profit_loss' => round(rand(-200, 300), 2),
                'result' => ['win', 'loss', 'break_even'][array_rand([0, 1, 2])],
                'strategy_used' => $strategies[array_rand($strategies)],
                'market_condition' => $marketConditions[array_rand($marketConditions)],
                'emotion_score' => rand(1, 5),
                'analysis' => 'Analisis manual dari observasi chart tanpa entry trade. Mempelajari pola market untuk referensi entry berikutnya.',
                'lesson_learned' => 'Market sedang ranging, baiknya wait di area supply/demand zone untuk mendapat entry terbaik.',
                'auto_imported' => false,
            ]);
        }

        $this->command->info('Dummy data seeded!');
        $this->command->info("- User: andriz@demo.com / password123");
        $this->command->info("- Trading Accounts: {$accountList->count()}");
        $this->command->info("- Trades: " . TradeHistory::where('user_id', $user->id)->count());
        $this->command->info("- Journal Entries: " . JournalEntry::where('user_id', $user->id)->count());
    }
}
