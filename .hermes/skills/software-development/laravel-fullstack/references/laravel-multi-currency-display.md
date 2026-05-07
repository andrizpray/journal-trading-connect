# Multi-Currency Display Pattern for Trading/Financial Apps

When an app handles multiple trading accounts with different base currencies (USD, IDR, EUR, GBP, Cent/USC), every P&L display must show which currency it's denominated in.

## Database Design

Add a `currency` column to the `trading_accounts` table:

```php
// Migration
Schema::table('trading_accounts', function (Blueprint $table) {
    $table->string('currency', 10)->default('USD')->after('platform');
});

// Model — add to $fillable
protected $fillable = [..., 'currency'];
```

Currency values: `USD`, `IDR`, `EUR`, `GBP`, `Cent` (for cent accounts where $1 = 100 cents).

## Currency Symbol Helper

**User preference: always use currency symbols ($, Rp, €, £, ¢) — never raw codes (USD, IDR).**

Create `app/Helpers/helpers.php`:

```php
<?php

function currency_symbol(?string $currency = null): string
{
    return match ($currency ?? 'USD') {
        'IDR'     => 'Rp',
        'EUR'     => '€',
        'GBP'     => '£',
        'Cent'    => '¢',
        'JPY'     => '¥',
        default   => '$',
    };
}
```

Register in `composer.json` autoload:

```json
{
    "autoload": {
        "files": ["app/Helpers/helpers.php"],
        "psr-4": { ... }
    }
}
```

Then run `composer dump-autoload`.

## Displaying Currency in Views

### P&L in tables (symbol prefix)

```blade
<td class="text-right font-bold whitespace-nowrap {{ $trade->profit_loss >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
    {{ $trade->profit_loss >= 0 ? '+' : '' }}{{ currency_symbol($trade->tradingAccount?->currency) }}{{ number_format($trade->profit_loss, 2, ',', '.') }}
</td>
```

Result: `+$150.00`, `Rp-50.000`, `€+12.50`

### Badge on account cards (symbol only)

```blade
<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-700/50 text-gray-300">
    {{ currency_symbol($account->currency) }}
</span>
```

### Account info (broker name only, no currency clutter)

```blade
<td>{{ $trade->tradingAccount?->broker ?? '-' }}</td>
```

### Currency selector in forms

```blade
<select name="currency" required class="dark-input w-full">
    <option value="USD">USD</option>
    <option value="IDR">IDR</option>
    <option value="EUR">EUR</option>
    <option value="GBP">GBP</option>
    <option value="Cent">Cent (USC)</option>
</select>
```

## Controller: Eager Loading

Always eager load the full chain when views access currency:

```php
$entries = JournalEntry::with('tradeHistory.tradingAccount')->paginate(20);
$recentTrades = TradeHistory::with('tradingAccount')->orderBy('close_date', 'desc')->take(10)->get();
```

## Aggregated P&L (Cross-Account)

When aggregating P&L across accounts with different currencies, either:
1. **Group by currency** — show separate totals per currency
2. **Show dominant currency** — if most accounts are USD, label the aggregate as "USD (approx.)"
3. **Hide currency on aggregates** — only show currency on per-trade/per-account P&L

## Files to Update When Adding Currency

- Migration: add `currency` column
- Model: add to `$fillable`
- Seeder: add `currency` to each account's data
- All views showing P&L: dashboard, trade-history, journal, trading-accounts
- Form for creating accounts: add currency dropdown
- Controller: ensure eager loading includes `tradingAccount` relation
