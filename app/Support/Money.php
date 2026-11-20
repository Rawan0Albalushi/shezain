<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function format(int $baisa): string
    {
        $sign = $baisa < 0 ? '-' : '';
        $baisa = abs($baisa);

        return $sign.sprintf('%d.%03d', intdiv($baisa, 1000), $baisa % 1000);
    }

    public static function fromOmr(string $amount): int
    {
        $normalized = str_replace(',', '', trim($amount));

        if (! preg_match('/^\d+(\.\d{1,3})?$/', $normalized)) {
            throw new InvalidArgumentException('Invalid OMR amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, 3), 3, '0');

        return ((int) $whole * 1000) + (int) $fraction;
    }
}
