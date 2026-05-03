<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyEmail;

#[Fillable(['name', 'email', 'password', 'role', 'leaderboard_opt_in', 'public_slug', 'public_profile_enabled', 'public_visible_fields', 'notify_daily_journal', 'notify_weekly_review', 'notify_trade_result', 'daily_journal_time', 'weekly_review_day', 'theme'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'leaderboard_opt_in' => 'boolean',
            'public_profile_enabled' => 'boolean',
            'public_visible_fields' => 'array',
            'notify_daily_journal' => 'boolean',
            'notify_weekly_review' => 'boolean',
            'notify_trade_result' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFieldVisible(string $field): bool
    {
        if (!$this->public_visible_fields) return false;
        return in_array($field, $this->public_visible_fields);
    }

    public function getPublicUrl(): ?string
    {
        if (!$this->public_slug || !$this->public_profile_enabled) return null;
        return url('/u/' . $this->public_slug);
    }

    public function tradingAccounts()
    {
        return $this->hasMany(TradingAccount::class);
    }

    public function tradeHistories()
    {
        return $this->hasMany(TradeHistory::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }
}
