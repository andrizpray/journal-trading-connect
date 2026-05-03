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
        'currency',
        'decimal_places_fx',
        'decimal_places_jpy',
        'decimal_places_metal',
        'api_token',
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

    protected $hidden = [
        'api_token',
    ];

    protected static function booted(): void
    {
        static::creating(function ($account) {
            if (empty($account->api_token)) {
                $account->api_token = bin2hex(random_bytes(32));
            }
        });
    }

    /**
     * Regenerate API token for EA Logger
     */
    public function regenerateToken(): string
    {
        $this->api_token = bin2hex(random_bytes(32));
        $this->save();
        return $this->api_token;
    }

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
