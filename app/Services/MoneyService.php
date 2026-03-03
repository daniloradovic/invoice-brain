<?php

namespace App\Services;

class MoneyService
{
    public static function format(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }

    public static function toCents(float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
