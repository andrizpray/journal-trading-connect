<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'user_id',
        'trade_history_id',
        'currency_pair',
        'trade_type',
        'profit_loss',
        'result',
        'analysis',
        'lesson_learned',
        'emotion_score',
        'market_condition',
        'strategy_used',
        'auto_imported',
        'screenshot_path',
        'tags',
        'template_type',
        'plan_setup',
        'plan_entry',
        'plan_sl',
        'plan_tp',
        'plan_reasoning',
    ];

    protected $casts = [
        'profit_loss' => 'decimal:2',
        'emotion_score' => 'integer',
        'auto_imported' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tradeHistory()
    {
        return $this->belongsTo(TradeHistory::class);
    }

    public function scopeWins($query)
    {
        return $query->where('result', 'win');
    }

    public function scopeLosses($query)
    {
        return $query->where('result', 'loss');
    }

    public function scopeAutoImported($query)
    {
        return $query->where('auto_imported', true);
    }

    // Parse comma-separated tags to array
    public function getTagListAttribute(): array
    {
        if (empty($this->tags)) return [];
        return array_map('trim', explode(',', $this->tags));
    }
}
