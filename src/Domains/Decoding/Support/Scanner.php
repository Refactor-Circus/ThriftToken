<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Support;

use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException;

/**
 * Quote-aware scanning of a TOON line (spec §5.2).
 *
 * A `"` opens a quoted span anywhere on a line and the next `"` that is not
 * part of an escape closes it; without one, the span runs to the end of the
 * line. Syntax characters inside a span are quoted, every other one is
 * unquoted. Syntax characters are ASCII, which never occurs inside a UTF-8
 * multibyte sequence, so scanning bytes is safe.
 */
final class Scanner
{
    /**
     * Positions of the first unquoted occurrence of each of `$chars`.
     *
     * @return array<string, int|null>
     */
    public static function first(string $line, string $chars): array
    {
        $found = array_fill_keys(str_split($chars), null);
        $remaining = count($found);
        $length = strlen($line);

        for ($i = 0; $i < $length && $remaining > 0; $i++) {
            $char = $line[$i];

            if ($char === '"') {
                $i = self::closingQuote($line, $i) ?? $length;

                continue;
            }

            if (array_key_exists($char, $found) && $found[$char] === null) {
                $found[$char] = $i;
                $remaining--;
            }
        }

        return $found;
    }

    /**
     * Split on every unquoted `$delimiter`, keeping empty tokens. Tokens are
     * returned untrimmed.
     *
     * @return list<string>
     */
    public static function split(string $line, string $delimiter): array
    {
        $tokens = [];
        $start = 0;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            if ($line[$i] === '"') {
                $i = self::closingQuote($line, $i) ?? $length;

                continue;
            }

            if ($line[$i] === $delimiter) {
                $tokens[] = substr($line, $start, $i - $start);
                $start = $i + 1;
            }
        }

        $tokens[] = substr($line, $start);

        return $tokens;
    }

    /**
     * The position of the quote closing the span opened at `$open`, or null
     * when the span runs to the end of the line.
     */
    public static function closingQuote(string $line, int $open): ?int
    {
        $length = strlen($line);

        for ($i = $open + 1; $i < $length; $i++) {
            if ($line[$i] === '\\') {
                $i++;

                continue;
            }

            if ($line[$i] === '"') {
                return $i;
            }
        }

        return null;
    }

    /**
     * Trim spaces, and no other character, from both ends (spec §1.2, §12).
     */
    public static function trim(string $token): string
    {
        return trim($token, ' ');
    }

    /**
     * Decode a key token: a quoted key is unescaped, any other token is the
     * key as written (spec §7.4).
     */
    public static function key(string $token, ?int $line): string
    {
        return str_starts_with($token, '"') ? self::quoted($token, $line) : $token;
    }

    /**
     * Unescape a token that starts with `"`. It must be one complete quoted
     * token: the closing quote has to be its last character (spec §7.4).
     */
    public static function quoted(string $token, ?int $line): string
    {
        $close = self::closingQuote($token, 0);

        if ($close === null) {
            throw new ToonDecodeException('Unterminated string', $line);
        }

        if ($close !== strlen($token) - 1) {
            throw new ToonDecodeException('Unexpected characters after a closing quote', $line);
        }

        return self::unescape(substr($token, 1, -1), $line);
    }

    /**
     * Resolve the escape sequences of spec §7.1.
     */
    private static function unescape(string $inner, ?int $line): string
    {
        if (! str_contains($inner, '\\')) {
            return $inner;
        }

        $result = '';
        $length = strlen($inner);

        for ($i = 0; $i < $length; $i++) {
            if ($inner[$i] !== '\\') {
                $result .= $inner[$i];

                continue;
            }

            $next = $inner[$i + 1] ?? '';

            if ($next === 'u') {
                $hex = substr($inner, $i + 2, 4);

                if (! preg_match('/^[0-9A-Fa-f]{4}$/D', $hex)) {
                    throw new ToonDecodeException('Invalid escape sequence: \\u needs four hex digits', $line);
                }

                $codepoint = (int) hexdec($hex);

                // Surrogates are rejected lone or paired; supplementary
                // characters are written as literal UTF-8 (spec §7.1).
                if ($codepoint >= 0xD800 && $codepoint <= 0xDFFF) {
                    throw new ToonDecodeException('Invalid escape sequence: surrogate \\u'.$hex, $line);
                }

                $result .= self::utf8($codepoint);
                $i += 5;

                continue;
            }

            $result .= match ($next) {
                '\\' => '\\',
                '"' => '"',
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                default => throw new ToonDecodeException('Invalid escape sequence: \\'.$next, $line),
            };
            $i++;
        }

        return $result;
    }

    private static function utf8(int $codepoint): string
    {
        return match (true) {
            $codepoint < 0x80 => chr($codepoint),
            $codepoint < 0x800 => chr(0xC0 | ($codepoint >> 6)).chr(0x80 | ($codepoint & 0x3F)),
            default => chr(0xE0 | ($codepoint >> 12)).chr(0x80 | (($codepoint >> 6) & 0x3F)).chr(0x80 | ($codepoint & 0x3F)),
        };
    }
}
