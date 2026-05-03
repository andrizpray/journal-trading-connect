<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'trading_account_id',
        'filename',
        'total_rows',
        'imported_count',
        'skipped_count',
        'error_count',
        'total_pnl',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_pnl' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tradingAccount()
    {
        return $this->belongsTo(TradingAccount::class);
    }
}
