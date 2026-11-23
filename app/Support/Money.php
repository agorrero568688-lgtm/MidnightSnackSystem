<?php

namespace App\Support;

/**
 * Currency helpers.
 *
 * Money is stored as numeric(12,2) in Postgres, but all arithmetic is done on
 * integer centavos so totals never drift. Never add floats directly.
 */
final class Money
{
    /** Parse a decimal amount ("25.50", "₱1,025.50", 25.5) into centavos. */
    public static function cents(mixed $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $string = trim((string) $amount);
        $negative = str_starts_with($string, '-');

        $string = (string) preg_replace('/[^0-9.]/', '', $string);

        if ($string === '' || $string === '.') {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $string, 2), 2, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($fraction, 3, '0');

        $cents = ((int) $whole) * 100 + (int) substr($fraction, 0, 2);

        if ((int) substr($fraction, 2, 1) >= 5) {
            $cents++;
        }

        return $negative ? -$cents : $cents;
    }

    /** Render centavos as a plain decimal string suitable for the database. */
    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Format centavos for display, e.g. ₱1,234.50 */
    public static function formatCents(int $cents): string
    {
        return self::symbol().number_format($cents / 100, 2);
    }

    /** Format a stored decimal amount for display. */
    public static function format(mixed $amount): string
    {
        return self::formatCents(self::cents($amount));
    }

    public static function symbol(): string
    {
        return '₱';
    }
}
