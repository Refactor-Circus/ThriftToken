<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Support;

/**
 * Decodes primitive value tokens (spec §4).
 *
 * Quoted tokens are unescaped strings. Unquoted `true`, `false` and `null`
 * are literals, a token matching the JSON number grammar is a number, and
 * anything else is a string. Tokens with a fraction or exponent decode as
 * floats. Integers beyond PHP's integer range decode as floats, and numbers
 * beyond the float range as their string token, so no value is silently
 * clamped.
 */
final class ValueDecoder
{
    private const string NUMBER = '/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/D';

    /**
     * Decode a token that has already been trimmed of spaces.
     */
    public static function decode(string $token, ?int $line = null): mixed
    {
        if (str_starts_with($token, '"')) {
            return Scanner::quoted($token, $line);
        }

        return match ($token) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => preg_match(self::NUMBER, $token) ? self::number($token) : $token,
        };
    }

    private static function number(string $token): int|float|string
    {
        if (strpbrk($token, '.eE') === false) {
            $int = (int) $token;

            // `-0` is 0; an integer beyond PHP's range falls through to float.
            if ((string) $int === $token || $token === '-0') {
                return $int;
            }
        }

        $float = (float) $token;

        if (is_infinite($float)) {
            return $token;
        }

        // -0.0 is 0 (spec §2).
        return $float == 0.0 ? 0.0 : $float;
    }
}
