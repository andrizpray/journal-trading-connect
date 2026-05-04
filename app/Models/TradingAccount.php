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
        'api_token_hash',
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
        'api_token_hash',
    ];

    public $plain_api_token;

    protected static function booted(): void
    {
        static::creating(function ($account) {
            if (empty($account->api_token_hash)) {
                $token = self::generateApiToken();
                $account->api_token_hash = hash('sha256', $token);
                $account->api_token = null;
                $account->plain_api_token = $token;
            }
        });
    }

    /**
     * Regenerate API token for EA Logger
     */
    public function regenerateToken(): string
    {
        $plainToken = self::generateApiToken();
        $this->api_token_hash = hash('sha256', $plainToken);
        $this->api_token = null;
        $this->save();

        return $plainToken;
    }

    public function matchesApiToken(string $plainToken): bool
    {
        return !empty($this->api_token_hash)
            && hash_equals($this->api_token_hash, hash('sha256', $plainToken));
    }

    private static function generateApiToken(): string
    {
        return bin2hex(random_bytes(32));
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
