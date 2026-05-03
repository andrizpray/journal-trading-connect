<?php

use App\Models\TradingAccount;

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

/**
 * Jumlah bertanda dengan simbol mata uang: -$11,80 (bukan $-11,80).
 */
function format_signed_currency(float|int|string|null $amount, ?string $currency = null, int $decimals = 2): string
{
    $n = (float) ($amount ?? 0);
    $symbol = currency_symbol($currency);
    $formatted = number_format(abs($n), $decimals, ',', '.');

    if ($n > 0) {
        return '+' . $symbol . $formatted;
    }
    if ($n < 0) {
        return '-' . $symbol . $formatted;
    }

    return '+' . $symbol . $formatted;
}

function normalize_currency_pair(?string $pair): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $pair ?? ''));
}

function is_metal_currency_pair(?string $pair): bool
{
    $n = normalize_currency_pair($pair);
    if ($n === '') {
        return false;
    }

    return (bool) preg_match('/^(XAU|XAG|XPT|XPD)/', $n)
        || str_starts_with($n, 'GOLD');
}

function is_jpy_currency_pair(?string $pair): bool
{
    return str_ends_with(normalize_currency_pair($pair), 'JPY');
}

/**
 * Jumlah digit desimal harga sesuai akun broker (FX / JPY / logam seperti XAUUSD).
 */
function trade_price_decimal_places(?string $currencyPair, ?TradingAccount $account = null): int
{
    if ($account !== null) {
        if (is_metal_currency_pair($currencyPair)) {
            return max(0, min(8, (int) $account->decimal_places_metal));
        }
        if (is_jpy_currency_pair($currencyPair)) {
            return max(0, min(8, (int) $account->decimal_places_jpy));
        }

        return max(0, min(8, (int) $account->decimal_places_fx));
    }

    if (is_metal_currency_pair($currencyPair)) {
        return 2;
    }
    if (is_jpy_currency_pair($currencyPair)) {
        return 3;
    }

    return 5;
}

/**
 * Format harga open/close (ribuan dengan koma, desimal titik — sama seperti number_format bawaan EN).
 */
function format_trade_price($price, ?string $currencyPair, ?TradingAccount $account = null): string
{
    if ($price === null || $price === '') {
        return '-';
    }

    $decimals = trade_price_decimal_places($currencyPair, $account);

    return number_format((float) $price, $decimals, '.', ',');
}
