<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeHistory extends Model
{
    protected $fillable = [
        'user_id',
        'trading_account_id',
        'ticket',
        'open_date',
        'close_date',
        'currency_pair',
        'trade_type',
        'lot_size',
        'open_price',
        'close_price',
        'stop_loss',
        'take_profit',
        'swap',
        'commission',
        'profit_loss',
        'result',
        'duration_minutes',
        'comment',
        'imported_at',
    ];

    protected $casts = [
        'open_date' => 'date',
        'close_date' => 'date',
        'lot_size' => 'decimal:2',
        'open_price' => 'decimal:8',
        'close_price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'swap' => 'decimal:2',
        'commission' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'duration_minutes' => 'integer',
        'imported_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tradingAccount()
    {
        return $this->belongsTo(TradingAccount::class);
    }

    public function journalEntry()
    {
        return $this->hasOne(JournalEntry::class);
    }

    public function scopeWins($query)
    {
        return $query->where('result', 'win');
    }

    public function scopeLosses($query)
    {
        return $query->where('result', 'loss');
    }
}
