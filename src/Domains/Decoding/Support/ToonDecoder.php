<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Support;

use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\Header;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\HeaderField;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonStrictModeException;

/**
 * Decodes TOON documents (spec v4.4) into PHP values.
 *
 * Objects decode to associative arrays (or stdClass objects when
 * `associative` is off), arrays to lists, and empty objects to stdClass.
 * Strict mode errors on every condition of spec §14; non-strict mode applies
 * exactly the five recoveries of §14.4 and errors on everything else.
 */
class ToonDecoder
{
    /** @var list<array{num: int, depth: int, content: string, blank: bool}> */
    private array $lines = [];

    private int $position = 0;

    /**
     * How many header spans (spec §12) enclose the line being read.
     */
    private int $spans = 0;

    public function __construct(
        private readonly DecoderOptions $options = new DecoderOptions,
    ) {}

    public function decode(string $toon): mixed
    {
        if (preg_match('//u', $toon) !== 1) {
            throw new ToonDecodeException('Input is not well-formed UTF-8');
        }

        $this->lines = $this->prepare($toon);
        $this->position = 0;
        $this->spans = 0;

        return DecodedObject::finalize($this->root(), $this->options->associative);
    }

    /**
     * Line preparation, in the order of spec §12.
     *
     * @return list<array{num: int, depth: int, content: string, blank: bool}>
     */
    private function prepare(string $toon): array
    {
        if (str_starts_with($toon, "\u{FEFF}")) {
            $toon = substr($toon, 3);
        }

        $lines = [];

        foreach (explode("\n", $toon) as $index => $line) {
            $num = $index + 1;

            if (str_ends_with($line, "\r")) {
                $line = substr($line, 0, -1);
            }

            $line = rtrim($line, ' ');

            // Comment lines vanish before anything else sees them (spec §5.1).
            if (str_starts_with(ltrim($line, ' '), '#')) {
                continue;
            }

            $indent = strspn($line, " \t");
            $content = substr($line, $indent);

            if ($content === '') {
                if ($line === '' || ! $this->options->strict) {
                    $lines[] = ['num' => $num, 'depth' => 0, 'content' => '', 'blank' => true];

                    continue;
                }
            }

            $lines[] = ['num' => $num, 'depth' => $this->depth(substr($line, 0, $indent), $num), 'content' => $content, 'blank' => false];
        }

        return $lines;
    }

    private function depth(string $indentation, int $line): int
    {
        $size = $this->options->indentSize;
        $tabs = substr_count($indentation, "\t");
        $spaces = strlen($indentation) - $tabs;

        if (! $this->options->strict) {
            return $tabs + intdiv($spaces, $size);
        }

        if ($tabs > 0) {
            throw new ToonStrictModeException('Tabs are not allowed in indentation', $line);
        }

        if ($spaces % $size !== 0) {
            throw new ToonStrictModeException("Indentation must be a multiple of $size spaces", $line);
        }

        return intdiv($spaces, $size);
    }

    /**
     * Root form discovery (spec §5).
     */
    private function root(): mixed
    {
        $index = $this->peek();

        if ($index === null) {
            return new DecodedObject($this->options->strict);
        }

        $line = $this->lines[$index];

        if ($line['depth'] > 0) {
            throw new ToonDecodeException('The first line must not be indented', $line['num']);
        }

        if (HeaderParser::isHeader($line['content'])) {
            $header = HeaderParser::parse($line['content'], $line['num'], $this->options->strict);

            if ($header->key === null) {
                $this->consume($index);

                return $this->complete($this->headerValue($header, 1, $line['num']));
            }
        }

        if ($line['content'] === '[]') {
            $this->consume($index);

            return $this->complete([]);
        }

        if ($this->nonBlankCount() === 1 && Scanner::first($line['content'], ':')[':'] === null) {
            $this->consume($index);

            return ValueDecoder::decode($line['content'], $line['num']);
        }

        $object = new DecodedObject($this->options->strict);
        $this->objectBody(0, $object);

        return $object;
    }

    /**
     * A root array or keyed object spans the whole document (spec §5).
     */
    private function complete(mixed $value): mixed
    {
        $index = $this->peek();

        if ($index !== null) {
            throw new ToonDecodeException('Unexpected content after the root value', $this->lines[$index]['num']);
        }

        return $value;
    }

    /**
     * Read an object's fields at `$depth` until a shallower line.
     */
    private function objectBody(int $depth, DecodedObject $object): void
    {
        while (($index = $this->peek()) !== null) {
            $line = $this->lines[$index];

            if ($line['depth'] < $depth) {
                return;
            }

            if ($line['depth'] > $depth) {
                throw new ToonDecodeException('Line is indented deeper than its scope', $line['num']);
            }

            $this->consume($index);
            $this->field($line['content'], $depth, $object, $line['num']);
        }
    }

    /**
     * Read one field line standing at `$depth`; any scope it opens has its
     * content at `$depth + 1`.
     */
    private function field(string $content, int $depth, DecodedObject $object, int $num): void
    {
        if (HeaderParser::isHeader($content)) {
            $header = HeaderParser::parse($content, $num, $this->options->strict);

            if ($header->key === null) {
                throw new ToonDecodeException('A keyless header is not valid in an object field position', $num);
            }

            $object->set($header->key, $this->headerValue($header, $depth + 1, $num), $num);

            return;
        }

        $colon = Scanner::first($content, ':')[':'];

        if ($colon === null) {
            throw new ToonDecodeException('Expected a key and a colon', $num);
        }

        $key = Scanner::key(Scanner::trim(substr($content, 0, $colon)), $num);
        $rest = Scanner::trim(substr($content, $colon + 1));

        $value = match ($rest) {
            '' => $this->nestedObject($depth + 1),
            '[]' => [],
            default => ValueDecoder::decode($rest, $num),
        };

        $object->set($key, $value, $num);
    }

    private function nestedObject(int $depth): DecodedObject
    {
        $object = new DecodedObject($this->options->strict);
        $content = $this->openScope($depth);

        if ($content !== null) {
            $this->objectBody($content, $object);
            $this->closeScope($depth, $content);
        }

        return $object;
    }

    /**
     * The value a header opens, with its scope content at `$depth`.
     */
    private function headerValue(Header $header, int $depth, int $num): mixed
    {
        return match (true) {
            $header->keyed => $this->keyedRows($header, $depth, $num),
            $header->fields !== null => $this->tabularRows($header, $depth, $num),
            $header->inline !== null => $this->inlineValues($header, $num),
            default => $this->listItems($header, $depth, $num),
        };
    }

    /**
     * @return list<mixed>
     */
    private function inlineValues(Header $header, int $num): array
    {
        $values = array_map(
            fn (string $token): mixed => ValueDecoder::decode(Scanner::trim($token), $num),
            Scanner::split((string) $header->inline, $header->delimiter->value),
        );

        $this->assertCount($header, count($values), $num);

        return $values;
    }

    /**
     * @return list<array<array-key, mixed>|DecodedObject>
     */
    private function tabularRows(Header $header, int $depth, int $num): array
    {
        $rows = [];
        $content = $this->openScope($depth);

        if ($content !== null) {
            $opened = false;

            while (($index = $this->peek()) !== null) {
                $line = $this->lines[$index];

                if ($line['depth'] < $content) {
                    break;
                }

                if ($line['depth'] > $content) {
                    throw new ToonDecodeException('Line is indented deeper than its scope', $line['num']);
                }

                // A row unless an unquoted colon comes before the first
                // unquoted delimiter (spec §9.3).
                $found = Scanner::first($line['content'], $header->delimiter->value.':');
                $colon = $found[':'];
                $delimiter = $found[$header->delimiter->value];

                if ($colon !== null && ($delimiter === null || $colon < $delimiter)) {
                    break;
                }

                $this->consume($index);
                if (! $opened) {
                    $this->spans++;
                    $opened = true;
                }
                $rows[] = $this->row($header, $line['content'], $line['num']);
            }

            if ($opened) {
                $this->spans--;
            }

            $this->closeScope($depth, $content);
        }

        $this->assertCount($header, count($rows), $num);

        return $rows;
    }

    private function keyedRows(Header $header, int $depth, int $num): DecodedObject
    {
        $object = new DecodedObject($this->options->strict);
        $count = 0;
        $content = $this->openScope($depth);

        if ($content !== null) {
            $opened = false;

            while (($index = $this->peek()) !== null) {
                $line = $this->lines[$index];

                if ($line['depth'] < $content) {
                    break;
                }

                if ($line['depth'] > $content) {
                    throw new ToonDecodeException('Line is indented deeper than its scope', $line['num']);
                }

                $colon = Scanner::first($line['content'], ':')[':'];

                if ($colon === null) {
                    throw new ToonDecodeException('Expected an entry key and a colon', $line['num']);
                }

                $this->consume($index);
                if (! $opened) {
                    $this->spans++;
                    $opened = true;
                }
                $count++;

                $key = Scanner::key(Scanner::trim(substr($line['content'], 0, $colon)), $line['num']);
                $cells = Scanner::trim(substr($line['content'], $colon + 1));

                $object->set($key, $this->row($header, $cells, $line['num']), $line['num']);
            }

            if ($opened) {
                $this->spans--;
            }

            $this->closeScope($depth, $content);
        }

        $this->assertCount($header, $count, $num);

        return $object;
    }

    /**
     * Decode a row or entry row's cells onto the header's field list.
     */
    private function row(Header $header, string $cells, int $num): DecodedObject
    {
        $tokens = $cells === '' ? [] : Scanner::split($cells, $header->delimiter->value);

        if (count($tokens) !== $header->leafCount()) {
            throw new ToonDecodeException(sprintf('Row has %d values but the header declares %d fields', count($tokens), $header->leafCount()), $num);
        }

        $values = array_map(fn (string $token): mixed => ValueDecoder::decode(Scanner::trim($token), $num), $tokens);
        $cursor = 0;

        return $this->materialize((array) $header->fields, $values, $cursor);
    }

    /**
     * @param  list<HeaderField>  $fields
     * @param  list<mixed>  $values
     */
    private function materialize(array $fields, array $values, int &$cursor): DecodedObject
    {
        // Repeated field names were rejected with the header in strict mode;
        // here the last one wins (spec §14.4).
        $object = new DecodedObject(false);

        foreach ($fields as $field) {
            $object->set($field->name, $field->children === null ? $values[$cursor++] : $this->materialize($field->children, $values, $cursor), null);
        }

        return $object;
    }

    /**
     * @return list<mixed>
     */
    private function listItems(Header $header, int $depth, int $num): array
    {
        $items = [];
        $content = $this->openScope($depth);

        if ($content !== null) {
            $opened = false;

            while (($index = $this->peek()) !== null) {
                $line = $this->lines[$index];

                if ($line['depth'] < $content) {
                    break;
                }

                if ($line['depth'] > $content) {
                    throw new ToonDecodeException('Line is indented deeper than its scope', $line['num']);
                }

                if ($line['content'] !== '-' && ! str_starts_with($line['content'], '- ')) {
                    break;
                }

                $this->consume($index);
                if (! $opened) {
                    $this->spans++;
                    $opened = true;
                }
                $items[] = $this->listItem($line['content'], $content, $line['num']);
            }

            if ($opened) {
                $this->spans--;
            }

            $this->closeScope($depth, $content);
        }

        $this->assertCount($header, count($items), $num);

        return $items;
    }

    /**
     * Decode a list item whose hyphen stands at `$depth` (spec §9.4, §10).
     */
    private function listItem(string $content, int $depth, int $num): mixed
    {
        $rest = ltrim(substr($content, 1), ' ');

        if ($rest === '') {
            return new DecodedObject($this->options->strict);
        }

        if ($rest === '[]') {
            return [];
        }

        if (HeaderParser::isHeader($rest)) {
            $header = HeaderParser::parse($rest, $num, $this->options->strict);

            // A keyless header is the item itself, standing at the hyphen's
            // depth; a keyed one is the first field of an object item.
            if ($header->key === null) {
                if ($header->fields !== null) {
                    throw new ToonDecodeException('A keyless header with a field list is only valid at the root', $num);
                }

                return $this->headerValue($header, $depth + 1, $num);
            }
        } elseif (Scanner::first($rest, ':')[':'] === null) {
            return ValueDecoder::decode($rest, $num);
        }

        // An object item's fields stand at depth + 1, the first of them on
        // the hyphen line.
        $object = new DecodedObject($this->options->strict);
        $this->field($rest, $depth + 1, $object, $num);
        $this->objectBody($depth + 1, $object);

        return $object;
    }

    /**
     * Find where a nested scope expecting content at `$depth` actually has
     * it: null when the scope is empty, `$depth` normally, or a deeper depth
     * under the non-strict depth-jump recovery (spec §8, §14.4).
     */
    private function openScope(int $depth): ?int
    {
        $index = $this->peek();

        if ($index === null || $this->lines[$index]['depth'] < $depth) {
            return null;
        }

        $line = $this->lines[$index];

        if ($line['depth'] > $depth && $this->options->strict) {
            throw new ToonStrictModeException('Indentation jumps more than one level', $line['num']);
        }

        return $line['depth'];
    }

    /**
     * After a depth-jumped scope, a line between the expected and the actual
     * content depth fits neither (spec §14.4).
     */
    private function closeScope(int $expected, int $actual): void
    {
        $index = $this->peek();

        if ($actual > $expected && $index !== null && $this->lines[$index]['depth'] >= $expected) {
            throw new ToonDecodeException('Line is shallower than its scope\'s content', $this->lines[$index]['num']);
        }
    }

    private function assertCount(Header $header, int $actual, int $num): void
    {
        if ($this->options->strict && $actual !== $header->length) {
            throw new ToonStrictModeException("Expected {$header->length} items but found $actual", $num);
        }
    }

    /**
     * The index of the next non-blank line, or null at the end.
     */
    private function peek(): ?int
    {
        for ($index = $this->position; $index < count($this->lines); $index++) {
            if (! $this->lines[$index]['blank']) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Take the line at `$index` into the current scope. Skipping a blank line
     * to reach it errors inside a header span (spec §12).
     */
    private function consume(int $index): void
    {
        if ($this->spans > 0 && $index > $this->position && $this->options->strict) {
            throw new ToonStrictModeException('Blank lines are not allowed inside an array', $this->lines[$this->position]['num']);
        }

        $this->position = $index + 1;
    }

    private function nonBlankCount(): int
    {
        return count(array_filter($this->lines, fn (array $line): bool => ! $line['blank']));
    }
}
