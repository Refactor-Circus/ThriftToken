<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Support;

use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonStrictModeException;
use stdClass;

/**
 * An object being decoded: ordered entries that stay distinct from a list
 * even when their keys look like list indexes.
 *
 * @internal
 */
final class DecodedObject
{
    /** @var array<array-key, mixed> */
    private array $entries = [];

    public function __construct(
        private readonly bool $strict,
    ) {}

    /**
     * Set an entry. A repeated key errors in strict mode; otherwise the last
     * value wins and the key keeps its first position (spec §14.3, §14.4).
     */
    public function set(string $key, mixed $value, ?int $line): void
    {
        if ($this->strict && array_key_exists($key, $this->entries)) {
            throw new ToonStrictModeException("Duplicate key \"$key\"", $line);
        }

        $this->entries[$key] = $value;
    }

    /**
     * Resolve to PHP values: an associative array per object, or a stdClass
     * when `$associative` is false. Empty objects are always a stdClass, so
     * they stay distinct from empty arrays.
     */
    public static function finalize(mixed $value, bool $associative): mixed
    {
        if ($value instanceof self) {
            $entries = array_map(fn (mixed $entry): mixed => self::finalize($entry, $associative), $value->entries);

            if ($entries === []) {
                return new stdClass;
            }

            if (! $associative) {
                foreach (array_keys($entries) as $key) {
                    // PHP reads a property name starting with NUL as a mangled
                    // private one, so a stdClass would silently drop the key.
                    if (str_starts_with((string) $key, "\0")) {
                        throw new ToonDecodeException('A key starting with a NUL character cannot be held by a stdClass; decode with associative: true');
                    }
                }
            }

            return $associative ? $entries : (object) $entries;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => self::finalize($item, $associative), $value);
        }

        return $value;
    }
}
