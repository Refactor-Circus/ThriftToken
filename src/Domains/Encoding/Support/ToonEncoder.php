<?php

namespace RefactorCircus\ThriftToken\Domains\Encoding\Support;

use BackedEnum;
use DateTimeInterface;
use JsonSerializable;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Exceptions\ToonEncodeException;
use stdClass;
use Stringable;
use Traversable;
use UnitEnum;

/**
 * Encodes PHP values as TOON (spec v4.4).
 *
 * Values are first normalized to the JSON data model (spec §3): lists stay
 * arrays, associative arrays and objects become objects, dates become ISO
 * 8601 strings, enums their value or name, and JsonSerializable objects
 * whatever they serialize to. The form of each value then follows from its
 * shape and position alone (spec §9): inline, list, tabular or keyed tabular.
 */
class ToonEncoder
{
    private string $delimiter;

    private string $symbol;

    public function __construct(
        private readonly EncoderOptions $options = new EncoderOptions,
    ) {
        $this->delimiter = $options->delimiter->value;
        $this->symbol = $options->delimiter->headerSymbol();
    }

    public function encode(mixed $data): string
    {
        return implode("\n", $this->root($this->normalize($data)));
    }

    /**
     * @return list<string>
     */
    private function root(mixed $value): array
    {
        if (is_array($value)) {
            return $value === [] ? ['[]'] : $this->array(null, $value, 0);
        }

        if (! $value instanceof ObjectValue) {
            return [$this->primitive($value, root: true)];
        }

        // An empty root object is an empty document (spec §8).
        if ($value->isEmpty()) {
            return [];
        }

        $fields = $this->keyedFields($value);

        if ($fields !== null) {
            return $this->keyed(null, $value, $fields, 0);
        }

        return $this->fields($value, 0);
    }

    /**
     * Every field of an object, at `$depth`.
     *
     * @return list<string>
     */
    private function fields(ObjectValue $object, int $depth): array
    {
        $lines = [];

        foreach ($object->entries as $key => $value) {
            array_push($lines, ...$this->field((string) $key, $value, $depth));
        }

        return $lines;
    }

    /**
     * One field standing at `$depth`, with any content it opens at
     * `$depth + 1` (spec §8).
     *
     * @return list<string>
     */
    private function field(string $key, mixed $value, int $depth): array
    {
        $key = ValueEncoder::encodeKey($key);
        $indent = $this->indent($depth);

        if (is_array($value)) {
            return $value === [] ? ["$indent$key: []"] : $this->array($key, $value, $depth);
        }

        if (! $value instanceof ObjectValue) {
            return ["$indent$key: ".$this->primitive($value)];
        }

        if ($value->isEmpty()) {
            return ["$indent$key:"];
        }

        $fields = $this->keyedFields($value);

        if ($fields !== null) {
            return $this->keyed($key, $value, $fields, $depth);
        }

        return ["$indent$key:", ...$this->fields($value, $depth + 1)];
    }

    /**
     * A non-empty array under an encoded key, or keyless at the root.
     *
     * @param  list<mixed>  $items
     * @return list<string>
     */
    private function array(?string $key, array $items, int $depth): array
    {
        $header = $this->indent($depth).($key ?? '').'['.count($items).$this->symbol.']';

        if ($this->allPrimitive($items)) {
            return ["$header: ".$this->join($items)];
        }

        $fields = $this->tabularFields($items);

        if ($fields !== null) {
            $lines = [$header.'{'.$this->fieldList($fields).'}:'];

            foreach ($items as $item) {
                $lines[] = $this->indent($depth + 1).implode($this->delimiter, $this->cells($item, $fields));
            }

            return $lines;
        }

        $lines = ["$header:"];

        foreach ($items as $item) {
            array_push($lines, ...$this->listItem($item, $depth + 1));
        }

        return $lines;
    }

    /**
     * A keyed tabular object (spec §9.5).
     *
     * @param  list<array{0: string, 1: array<mixed>|null}>  $fields
     * @return list<string>
     */
    private function keyed(?string $key, ObjectValue $object, array $fields, int $depth): array
    {
        $lines = [$this->indent($depth).($key ?? '').'['.count($object->entries).':'.$this->symbol.']{'.$this->fieldList($fields).'}:'];

        foreach ($object->entries as $entryKey => $value) {
            $lines[] = $this->indent($depth + 1).ValueEncoder::encodeKey((string) $entryKey).': '.implode($this->delimiter, $this->cells($value, $fields));
        }

        return $lines;
    }

    /**
     * A list item whose hyphen stands at `$depth` (spec §9.4, §10).
     *
     * @return list<string>
     */
    private function listItem(mixed $item, int $depth): array
    {
        $indent = $this->indent($depth);

        if (is_array($item)) {
            $header = $indent.'- ['.count($item).$this->symbol.']:';

            if ($this->allPrimitive($item)) {
                return [$item === [] ? $header : "$header ".$this->join($item)];
            }

            // Tabular form is not available here: a keyless field list is
            // only valid at the root (spec §9.4).
            $lines = [$header];

            foreach ($item as $nested) {
                array_push($lines, ...$this->listItem($nested, $depth + 1));
            }

            return $lines;
        }

        if (! $item instanceof ObjectValue) {
            return ["$indent- ".$this->primitive($item)];
        }

        if ($item->isEmpty()) {
            return ["$indent-"];
        }

        // The first field stands on the hyphen line at depth + 1, the rest
        // of the fields below it at the same depth.
        $lines = [];

        foreach ($item->entries as $key => $value) {
            array_push($lines, ...$this->field((string) $key, $value, $depth + 1));
        }

        $lines[0] = "$indent- ".substr($lines[0], strlen($this->indent($depth + 1)));

        return $lines;
    }

    /**
     * The field list of an array encodable in tabular form, or null.
     *
     * @param  list<mixed>  $items
     * @return list<array{0: string, 1: array<mixed>|null}>|null
     */
    private function tabularFields(array $items): ?array
    {
        foreach ($items as $item) {
            if (! $item instanceof ObjectValue || $item->isEmpty()) {
                return null;
            }
        }

        return $this->fieldTree($items);
    }

    /**
     * The field list of an object encodable in keyed tabular form, or null.
     *
     * @return list<array{0: string, 1: array<mixed>|null}>|null
     */
    private function keyedFields(ObjectValue $object): ?array
    {
        if (count($object->entries) < 2) {
            return null;
        }

        $values = array_values($object->entries);

        foreach ($values as $value) {
            if (! $value instanceof ObjectValue || $value->isEmpty()) {
                return null;
            }
        }

        return $this->fieldTree($values);
    }

    /**
     * The shared field list of non-empty objects with one key set, where
     * every column is uniform-primitive or nested-uniform (spec §9.3), in
     * the first object's key order.
     *
     * @param  list<ObjectValue>  $objects
     * @return list<array{0: string, 1: array<mixed>|null}>|null
     */
    private function fieldTree(array $objects): ?array
    {
        $keySet = $objects[0]->keySet();

        foreach ($objects as $object) {
            if ($object->keySet() !== $keySet) {
                return null;
            }
        }

        $tree = [];

        foreach (array_keys($objects[0]->entries) as $key) {
            $column = array_map(fn (ObjectValue $object): mixed => $object->entries[$key], $objects);

            if ($this->allPrimitive($column)) {
                $tree[] = [(string) $key, null];

                continue;
            }

            foreach ($column as $value) {
                if (! $value instanceof ObjectValue || $value->isEmpty()) {
                    return null;
                }
            }

            $children = $this->fieldTree($column);

            if ($children === null) {
                return null;
            }

            $tree[] = [(string) $key, $children];
        }

        return $tree;
    }

    /**
     * @param  list<array{0: string, 1: array<mixed>|null}>  $fields
     */
    private function fieldList(array $fields): string
    {
        return implode($this->delimiter, array_map(
            fn (array $field): string => ValueEncoder::encodeKey($field[0]).($field[1] === null ? '' : '{'.$this->fieldList($field[1]).'}'),
            $fields,
        ));
    }

    /**
     * An object's leaf values in depth-first order of the field list.
     *
     * @param  list<array{0: string, 1: array<mixed>|null}>  $fields
     * @return list<string>
     */
    private function cells(ObjectValue $object, array $fields): array
    {
        $cells = [];

        foreach ($fields as [$name, $children]) {
            $value = $object->entries[$name];

            if ($children === null) {
                $cells[] = $this->primitive($value);
            } else {
                /** @var ObjectValue $value */
                array_push($cells, ...$this->cells($value, $children));
            }
        }

        return $cells;
    }

    /**
     * @param  list<mixed>  $items
     */
    private function join(array $items): string
    {
        return implode($this->delimiter, array_map(fn (mixed $item): string => $this->primitive($item), $items));
    }

    private function primitive(mixed $value, bool $root = false): string
    {
        return ValueEncoder::encode($value, $this->options->delimiter, $root);
    }

    /**
     * @param  array<mixed>  $values
     */
    private function allPrimitive(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value) || $value instanceof ObjectValue) {
                return false;
            }
        }

        return true;
    }

    private function indent(int $depth): string
    {
        return str_repeat(' ', $depth * $this->options->indentSize);
    }

    /**
     * Normalize to the JSON data model (spec §3).
     */
    private function normalize(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $this->wellFormed($value);
        }

        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }

            // -0 becomes 0, and integral floats print as integers (spec §2).
            if (floor($value) === $value && abs($value) < PHP_INT_MAX) {
                return (int) $value;
            }

            return $value;
        }

        if (is_array($value)) {
            return array_is_list($value)
                ? array_map(fn (mixed $item): mixed => $this->normalize($item), $value)
                : $this->object($value);
        }

        if ($value instanceof stdClass) {
            return $this->object(get_object_vars($value));
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $this->normalize($value->value);
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalize($value->jsonSerialize());
        }

        if ($value instanceof Stringable) {
            return $this->normalize((string) $value);
        }

        if ($value instanceof Traversable) {
            return $this->normalize(iterator_to_array($value));
        }

        if (is_object($value)) {
            return $this->object(get_object_vars($value));
        }

        throw new ToonEncodeException('Cannot encode value of type: '.get_debug_type($value));
    }

    /**
     * @param  array<array-key, mixed>  $entries
     */
    private function object(array $entries): ObjectValue
    {
        $normalized = [];

        foreach ($entries as $key => $value) {
            $normalized[is_string($key) ? $this->wellFormed($key) : $key] = $this->normalize($value);
        }

        return new ObjectValue($normalized);
    }

    /**
     * Strings, keys included, must be Unicode scalar values (spec §3).
     */
    private function wellFormed(string $string): string
    {
        if (preg_match('//u', $string) !== 1) {
            throw new ToonEncodeException('Strings and keys must be well-formed UTF-8');
        }

        return $string;
    }
}
