<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Support;

use RefactorCircus\ThriftToken\Domains\Decoding\Data\Header;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\HeaderField;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonStrictModeException;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

/**
 * Parses array and keyed headers per the grammar of spec §6.
 *
 * Only lines whose first unquoted `[` precedes their first unquoted colon
 * reach this parser (spec §5.2); every such line must match the grammar.
 */
final class HeaderParser
{
    private const string DELIMITERS = ",\t|";

    /**
     * Whether a line's content is an array-header line (spec §5.2).
     */
    public static function isHeader(string $content): bool
    {
        $found = Scanner::first($content, '[:');

        return $found[':'] !== null && $found['['] !== null && $found['['] < $found[':'];
    }

    public static function parse(string $content, ?int $line, bool $strict): Header
    {
        $open = (int) Scanner::first($content, '[')['['];
        $rawKey = substr($content, 0, $open);

        if ($rawKey !== '' && strspn(strrev($rawKey), " \t") > 0) {
            throw new ToonDecodeException('Whitespace between a key and its bracket segment', $line);
        }

        $key = $rawKey === '' ? null : Scanner::key($rawKey, $line);

        if (! preg_match('/\G\[(0|[1-9][0-9]*)(:?)(\t|\|)?\]/', $content, $match, 0, $open)) {
            throw new ToonDecodeException('Malformed bracket segment in array header', $line);
        }

        // No upper bound: a length beyond PHP_INT_MAX still forms a header,
        // whose count is simply never met (spec §6).
        $length = strlen($match[1]) > 18 ? PHP_INT_MAX : (int) $match[1];
        $keyed = $match[2] === ':';
        $delimiter = Delimiter::from(($match[3] ?? '') === '' ? ',' : $match[3]);
        $position = $open + strlen($match[0]);
        $fields = null;

        if (($content[$position] ?? '') === '{') {
            [$fields, $position] = self::fields($content, $position, $delimiter, $line, $strict);
        }

        if (($content[$position] ?? '') !== ':') {
            throw new ToonDecodeException('Array header must end with a colon', $line);
        }

        if ($keyed && $fields === null) {
            throw new ToonDecodeException('A keyed header needs a field list', $line);
        }

        $rest = Scanner::trim(substr($content, $position + 1));

        if ($fields !== null && $rest !== '') {
            throw new ToonDecodeException('Unexpected content after a field list header', $line);
        }

        return new Header($key, $length, $keyed, $delimiter, $fields, $rest === '' ? null : $rest);
    }

    /**
     * Parse the field list opening at `$open`, returning its entries and the
     * position after its closing brace.
     *
     * @return array{0: list<HeaderField>, 1: int}
     */
    private static function fields(string $content, int $open, Delimiter $delimiter, ?int $line, bool $strict): array
    {
        $fields = [];
        $names = [];
        $length = strlen($content);
        $position = $open + 1;

        while (true) {
            $start = $position;

            while ($position < $length) {
                $char = $content[$position];

                if ($char === '"') {
                    $position = (Scanner::closingQuote($content, $position) ?? $length) + 1;

                    continue;
                }

                if ($char === '{' || $char === '}' || $char === $delimiter->value) {
                    break;
                }

                if (str_contains(self::DELIMITERS, $char)) {
                    throw new ToonDecodeException('Field list uses a delimiter other than the header\'s', $line);
                }

                $position++;
            }

            if ($position >= $length) {
                throw new ToonDecodeException('Unmatched brace in field list', $line);
            }

            $raw = substr($content, $start, $position - $start);
            $token = Scanner::trim($raw);

            if ($token === '') {
                throw new ToonDecodeException('Empty field entry in field list', $line);
            }

            $children = null;

            if ($content[$position] === '{') {
                if (strspn(strrev($raw), " \t") > 0) {
                    throw new ToonDecodeException('Whitespace between a field name and its nested field group', $line);
                }

                [$children, $position] = self::fields($content, $position, $delimiter, $line, $strict);
                $position += strspn($content, ' ', $position);
            }

            $name = Scanner::key($token, $line);

            if (isset($names[$name]) && $strict) {
                throw new ToonStrictModeException("Duplicate field name \"$name\" in field list", $line);
            }

            $names[$name] = true;
            $fields[] = new HeaderField($name, $children);

            $char = $content[$position] ?? '';

            if ($char === $delimiter->value) {
                $position++;

                continue;
            }

            if ($char === '}') {
                return [$fields, $position + 1];
            }

            throw new ToonDecodeException('Malformed field list', $line);
        }
    }
}
