<?php

namespace RefactorCircus\ThriftToken\Domains\Encoding\Support;

use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

/**
 * Encodes primitive values and keys.
 *
 * Strings are quoted per spec §7.2 and escaped per §7.1; keys are quoted
 * unless they match the unquoted-key pattern of §7.3.
 */
class ValueEncoder
{
    /**
     * Encode a primitive, quoting strings against the document delimiter.
     *
     * @param  bool  $root  Whether the value is a root primitive, which quotes a leading U+FEFF.
     */
    public static function encode(mixed $value, Delimiter $delimiter, bool $root = false): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return NumberEncoder::encode($value) ?? 'null';
        }

        $string = (string) $value;

        return self::needsQuoting($string, $delimiter, $root) ? self::quote($string) : $string;
    }

    /**
     * Encode an object key, entry key or field name (spec §7.3).
     */
    public static function encodeKey(string $key): string
    {
        // /D keeps a trailing newline from satisfying `$`.
        return preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/D', $key) ? $key : self::quote($key);
    }

    public static function needsQuoting(string $value, Delimiter $delimiter, bool $root = false): bool
    {
        return $value === ''
            || strspn($value, " \t") > 0
            || strspn(strrev($value), " \t") > 0
            || in_array($value, ['true', 'false', 'null'], true)
            || preg_match('/^[+-]?[0-9]+(?:\.[0-9]+)?(?:e[+-]?[0-9]+)?$/iD', $value) === 1
            || strpbrk($value, ':"\\[]{}') !== false
            || preg_match('/[\x00-\x1F]/', $value) === 1
            || str_contains($value, $delimiter->value)
            || $value[0] === '-'
            || $value[0] === '#'
            || ($root && str_starts_with($value, "\u{FEFF}"));
    }

    /**
     * Quote and escape a string (spec §7.1).
     */
    public static function quote(string $value): string
    {
        $escaped = preg_replace_callback(
            '/[\x00-\x1F"\\\\]/',
            fn (array $match): string => match ($match[0]) {
                '\\' => '\\\\',
                '"' => '\\"',
                "\n" => '\\n',
                "\r" => '\\r',
                "\t" => '\\t',
                default => sprintf('\\u%04x', ord($match[0])),
            },
            $value,
        );

        return '"'.$escaped.'"';
    }
}
