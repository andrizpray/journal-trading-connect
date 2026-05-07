# Laravel Multi-User Features: Leaderboard, Public Profiles, Seeder Patterns

Patterns used in Journal Trading Connect (Phase 5) for multi-user social features.

## Leaderboard (Opt-In Ranking)

**Migration:** boolean column `leaderboard_opt_in` (default false) on users table.

**Controller pattern:**
- Filter users: `User::where('leaderboard_opt_in', true)->whereHas('tradeHistories', ...)`
- Period filtering via date range on trades (all_time/weekly/monthly/quarterly/yearly)
- Aggregate with `withCount` + `withSum` for total_trades, wins, losses, P&L
- Sort by configurable field (win_rate, total_pnl, total_trades, avg_pnl)
- Calculate rank after sorting (map with index)
- Find current user's rank from collection

**Pitfall:** `withSum` for profit_factor requires two separate sums (wins_pnl, losses_pnl). Don't try to calculate from `avg_loss` on the collection — that field doesn't exist on the aggregate model.

```php
->withSum([
    'tradeHistories as wins_pnl' => fn($q) => $q->where('result', 'win'),
    'tradeHistories as losses_pnl' => fn($q) => $q->where('result', 'loss'),
], 'profit_loss')
// Then: profitFactor = totalWinPnl / abs(totalLossPnl)
```

**View:** Podium top 3 (2nd-1st-3rd layout with emoji medals), full table below.

**Toggle route:** POST to toggle opt-in, redirect back with flash message.

## Public Profile (Slug-Based, Configurable Visibility)

**Migration:** three columns on users table:
```php
$table->string('public_slug')->unique()->nullable();
$table->boolean('public_profile_enabled')->default(false);
$table->json('public_visible_fields')->nullable();
```

**Slug generation (on enable):**
```php
$slug = Str::lower(Str::slug($user->name) . '-' . Str::random(6));
while (User::where('public_slug', $slug)->exists()) {
    $slug = Str::lower(Str::slug($user->name) . '-' . Str::random(6));
}
```

**Route conflict with Breeze `/profile`:**
Breeze registers `GET /profile` → `ProfileController@edit`. A `GET /profile/{slug}` route for public profiles will conflict. Use a different prefix like `/u/{slug}`.

**Pitfall:** Always verify `php artisan route:list | grep profile` after adding public profile routes — look for duplicate route registrations or conflicts.

**Visibility control pattern:**
- `public_visible_fields` stores array of allowed field names as JSON: `['total_trades', 'win_rate', 'total_pnl', ...]`
- **CRITICAL:** Cast this column to `array` in the User model's `casts()` method: `'public_visible_fields' => 'array'`. Without this cast, Eloquent returns a raw JSON string, and `in_array()` throws `TypeError: Argument #2 must be of type array, string given`. The `?? []` fallback doesn't help because the string is truthy.
- Model helper: `isFieldVisible(string $field): bool` — checks `in_array($field, $this->public_visible_fields)`
- Controller only computes data for visible fields to avoid unnecessary queries
- View checks `in_array('field_name', $visibleFields)` before rendering each section

**Settings page:** checkbox list of available fields, save/enable/disable/regenerate slug.

**Public show page:** standalone layout (NOT extending app layout — no auth, no sidebar), loads Chart.js via CDN for equity curve, minimal footer.

**`getPublicUrl()` helper on model:**
```php
public function getPublicUrl(): ?string {
    if (!$this->public_slug || !$this->public_profile_enabled) return null;
    return url('/u/' . $this->public_slug);
}
```

## Seeder Ticket Uniqueness

**Problem:** `TradeHistory` has a unique index on `(user_id, trading_account_id, ticket)`. Using `rand()` for tickets in seeders causes collisions when seeding multiple users with many trades.

**Solution:** Use deterministic ticket based on user_id + index:
```php
'ticket' => (int) ($user->id . str_pad((string) $i, 6, '0', STR_PAD_LEFT)),
// User 2, trade 5 → 2000005
// User 7, trade 142 → 7000142
```

**Pitfall:** `rand(2000000, 9999999)` is NOT safe for multi-user seeders — collisions cause `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`.

## Admin User Management (Column-Based Role)

See the main SKILL.md "Column-Based Role System" section for the full pattern. Key additions from this implementation:

**AdminController features:**
- Dashboard: global stats (total users, trades, P&L, accounts, journals), recent users, top users by P&L, recent imports
- User list: search by name/email, filter by role, per-user stats (trades, accounts, P&L, win rate, journals)
- Role change: inline `<select>` with `onchange="this.form.submit()"` — no separate page needed
- User detail: aggregated view of one user's accounts, recent trades, recent journals
- Delete: cascade delete all user data (journal entries, trade histories, trading accounts, import logs)

**Pitfall:** Admin role change/delete must prevent self-modification: `if ((int) $id === Auth::id()) abort()`.

**Sidebar pattern:** `@auth @if(auth()->user()->isAdmin())` with amber shield icon — visually distinct from regular menu items.

## `#[Fillable]` PHP 8.3 Attribute (Laravel 11+)

This project uses PHP 8.3 attributes for fillable instead of the traditional `$fillable` property:

```php
// Laravel 11+ style
#[Fillable(['name', 'email', 'password', 'role'])]
class User extends Authenticatable { }

// Traditional (still supported)
class User extends Authenticatable {
    protected $fillable = ['name', 'email', 'password', 'role'];
}
```

Both work. When reading models, check which style is used before patching.
