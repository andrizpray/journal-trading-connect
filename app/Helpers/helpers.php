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
