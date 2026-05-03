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
        // 1. User (akun khusus demo — aman di-seed ulang)
        $user = User::firstOrCreate(
            ['email' => 'andriz@demo.com'],
            [
                'name' => 'Andriz',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );

        // Bersihkan data lama user demo agar --class=DummyDataSeeder idempotent
        JournalEntry::where('user_id', $user->id)->delete();
        TradeHistory::where('user_id', $user->id)->delete();
        TradingAccount::where('user_id', $user->id)->update([
            'total_trades' => 0,
            'total_pnl' => 0,
            'last_synced_at' => null,
        ]);

        // 2. Trading Accounts
        $accounts = [
            [
                'account_number' => '12345678',
                'account_name' => 'Akun Utama',
                'broker' => 'OctaFX',
                'platform' => 'mt5',
                'currency' => 'USD',
            ],
            [
                'account_number' => '87654321',
                'account_name' => 'Akun Scalping',
                'broker' => 'XM',
                'platform' => 'mt5',
                'currency' => 'USD',
            ],
            [
                'account_number' => '11223344',
                'account_name' => null,
                'broker' => 'ICMarkets',
                'platform' => 'mt4',
                'currency' => 'USD',
            ],
        ];

        foreach ($accounts as $accData) {
            TradingAccount::firstOrCreate(
                ['user_id' => $user->id, 'account_number' => $accData['account_number']],
                $accData + ['user_id' => $user->id, 'sync_method' => 'csv', 'is_active' => true]
            );
        }

        $accountList = TradingAccount::where('user_id', $user->id)->get();

        // Contoh: satu broker dengan emas 3 digit di belakang koma
        TradingAccount::where('user_id', $user->id)->where('broker', 'ICMarkets')->update(['decimal_places_metal' => 3]);

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
        $this->command->info("- User: andriz@demo.com / password123 (admin)");
        $this->command->info("- Trading Accounts: {$accountList->count()}");
        $this->command->info("- Trades: " . TradeHistory::where('user_id', $user->id)->count());
        $this->command->info("- Journal Entries: " . JournalEntry::where('user_id', $user->id)->count());

        // 6. Additional dummy users for leaderboard
        $dummyUsers = [
            ['name' => 'Budi Santoso', 'email' => 'budi@demo.com'],
            ['name' => 'Siti Rahmawati', 'email' => 'siti@demo.com'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky@demo.com'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@demo.com'],
            ['name' => 'Ahmad Hidayat', 'email' => 'ahmad@demo.com'],
        ];

        foreach ($dummyUsers as $userData) {
            $dummyUser = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                    'role' => 'user',
                    'leaderboard_opt_in' => true,
                ]
            );

            // Skip if user already has trades
            if (TradeHistory::where('user_id', $dummyUser->id)->count() > 0) {
                continue;
            }

            // Create 1 trading account
            $brokers = ['Exness', 'FBS', 'XM', 'ICMarkets', 'Pepperstone'];
            $broker = $brokers[array_rand($brokers)];
            $dummyAcc = TradingAccount::create([
                'user_id' => $dummyUser->id,
                'account_number' => str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'broker' => $broker,
                'platform' => ['mt4', 'mt5'][array_rand([0, 1])],
                'currency' => 'USD',
                'sync_method' => 'csv',
                'is_active' => true,
            ]);

            // Create random trades (30-120 trades per user)
            $numTrades = rand(30, 120);
            $pairs = ['EURUSD', 'GBPUSD', 'USDJPY', 'XAUUSD', 'GBPJPY', 'AUDUSD', 'USDCAD', 'NZDUSD', 'EURGBP', 'EURJPY'];
            $basePrices = [
                'EURUSD' => 1.08500, 'GBPUSD' => 1.26500, 'USDJPY' => 149.500, 'XAUUSD' => 2340.00,
                'GBPJPY' => 189.200, 'AUDUSD' => 0.65500, 'USDCAD' => 1.36500, 'NZDUSD' => 0.61000,
                'EURGBP' => 0.85800, 'EURJPY' => 162.200,
            ];

            // Each user has a bias — some profitable, some not, to make it realistic
            $winRateBias = rand(-15, 25); // -15% to +25% bias on base 50%
            $pnlMultiplier = 0.5 + (rand(0, 150) / 100); // 0.5x to 2.0x P&L multiplier

            for ($i = 0; $i < $numTrades; $i++) {
                $daysAgo = rand(1, 90);
                $openDate = now()->subDays($daysAgo)->setHour(rand(6, 20))->setMinute(rand(0, 59));
                $durationMinutes = rand(5, 1440);
                $closeDate = (clone $openDate)->addMinutes($durationMinutes);

                $pair = $pairs[array_rand($pairs)];
                $isBuy = rand(0, 1) === 1;
                $lot = [0.01, 0.02, 0.05, 0.10, 0.20][array_rand([0, 1, 2, 3, 4])];

                $basePrice = $basePrices[$pair] ?? 1.0;
                $pipMultiplier = in_array($pair, ['USDJPY', 'GBPJPY', 'XAUUSD', 'EURJPY']) ? 0.01 : 0.0001;
                $pipMove = rand(-80, 80);
                $openPrice = $basePrice + ($pipMove * $pipMultiplier * rand(1, 5));
                $closePrice = $openPrice + (rand(-50, 50) * $pipMultiplier);

                $pipsMoved = ($closePrice - $openPrice) / $pipMultiplier;
                $profitPips = $isBuy ? $pipsMoved : -$pipsMoved;
                $pipValue = match ($pair) {
                    'XAUUSD' => $lot * 1.0,
                    'USDJPY', 'GBPJPY', 'EURJPY' => $lot * 0.67,
                    default => $lot * 10.0,
                };

                // Apply bias — higher bias = more winning trades
                $biasedProfit = $profitPips * $pipValue * $pnlMultiplier;
                // Add small random noise
                $profitLoss = round($biasedProfit + rand(-10, 10), 2);

                $result = $profitLoss > 0 ? 'win' : ($profitLoss < 0 ? 'loss' : 'break_even');

                TradeHistory::create([
                    'user_id' => $dummyUser->id,
                    'trading_account_id' => $dummyAcc->id,
                    'ticket' => (int) ($dummyUser->id . str_pad((string) $i, 6, '0', STR_PAD_LEFT)),
                    'open_date' => $openDate->format('Y-m-d'),
                    'close_date' => $closeDate->format('Y-m-d'),
                    'currency_pair' => $pair,
                    'trade_type' => $isBuy ? 'buy' : 'sell',
                    'lot_size' => $lot,
                    'open_price' => $openPrice,
                    'close_price' => $closePrice,
                    'stop_loss' => $isBuy ? round($openPrice - rand(10, 30) * $pipMultiplier, 5) : round($openPrice + rand(10, 30) * $pipMultiplier, 5),
                    'take_profit' => $isBuy ? round($openPrice + rand(10, 30) * $pipMultiplier, 5) : round($openPrice - rand(10, 30) * $pipMultiplier, 5),
                    'swap' => round(rand(-30, 10) * 0.01 * $lot, 2),
                    'commission' => round($lot * 7.0, 2),
                    'profit_loss' => $profitLoss,
                    'result' => $result,
                    'duration_minutes' => $durationMinutes,
                    'imported_at' => now(),
                    'created_at' => $closeDate,
                    'updated_at' => $closeDate,
                ]);
            }

            // Update account stats
            $dummyAcc->update([
                'total_trades' => TradeHistory::where('trading_account_id', $dummyAcc->id)->count(),
                'total_pnl' => TradeHistory::where('trading_account_id', $dummyAcc->id)->sum('profit_loss'),
                'last_synced_at' => now()->subHours(rand(1, 48)),
            ]);
        }

        $this->command->info('- Leaderboard users: 5 additional dummy users');
    }
}
