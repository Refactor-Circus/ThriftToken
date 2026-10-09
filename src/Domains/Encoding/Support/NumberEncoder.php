<?php

namespace RefactorCircus\ThriftToken\Domains\Encoding\Support;

/**
 * Encodes numbers in canonical form (spec §2).
 *
 * Plain decimal with no leading or trailing zeros when the value is 0 or its
 * magnitude is in [1e-6, 1e21); JSON exponent form with a lowercase `e` and
 * an explicit sign otherwise. Floats use the fewest significant digits that
 * round-trip, closest to the value. NaN and infinities return null, which the
 * caller writes as `null`.
 */
class NumberEncoder
{
    public static function encode(int|float $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_nan($value) || is_infinite($value)) {
            return null;
        }

        if ($value == 0.0) {
            return '0';
        }

        [$digits, $exponent] = self::shortest(abs($value));
        $sign = $value < 0 ? '-' : '';

        // $digits holds the significant digits d1d2…dn, and the value is
        // 0.d1d2…dn × 10^$exponent.
        if (abs($value) >= 1e-6 && abs($value) < 1e21) {
            return $sign.self::plain($digits, $exponent);
        }

        $mantissa = strlen($digits) === 1 ? $digits : $digits[0].'.'.substr($digits, 1);
        $power = $exponent - 1;

        return $sign.$mantissa.'e'.($power < 0 ? '-' : '+').abs($power);
    }

    /**
     * The shortest round-trip significant digits of a positive float, and its
     * decimal exponent, from PHP's own shortest conversion (serialize
     * precision -1).
     *
     * @return array{0: string, 1: int}
     */
    private static function shortest(float $value): array
    {
        $previous = ini_set('serialize_precision', '-1');

        try {
            $repr = var_export($value, true);
        } finally {
            if ($previous !== false) {
                ini_set('serialize_precision', $previous);
            }
        }

        preg_match('/^(\d+)(?:\.(\d+))?(?:E([+-]?\d+))?$/D', $repr, $match);

        $integer = $match[1];
        $fraction = $match[2] ?? '';
        $all = ltrim($integer.$fraction, '0');
        $leadingZeros = strlen($integer.$fraction) - strlen($all);
        $exponent = strlen($integer) - $leadingZeros + (int) ($match[3] ?? 0);

        return [rtrim($all, '0'), $exponent];
    }

    private static function plain(string $digits, int $exponent): string
    {
        if ($exponent <= 0) {
            return '0.'.str_repeat('0', -$exponent).$digits;
        }

        if ($exponent >= strlen($digits)) {
            return $digits.str_repeat('0', $exponent - strlen($digits));
        }

        return substr($digits, 0, $exponent).'.'.substr($digits, $exponent);
    }
}
