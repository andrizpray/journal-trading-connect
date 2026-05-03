<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'broker',
        'platform',
        'sync_method',
        'is_active',
        'last_synced_at',
        'total_trades',
        'total_pnl',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'total_pnl' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tradeHistories()
    {
        return $this->hasMany(TradeHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
