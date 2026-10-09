<?php

namespace RefactorCircus\ThriftToken\Domains\Encoding\Support;

/**
 * A normalized object: ordered entries that stay an object even when their
 * keys look like list indexes.
 *
 * @internal
 */
final readonly class ObjectValue
{
    /**
     * @param  array<array-key, mixed>  $entries
     */
    public function __construct(
        public array $entries,
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /**
     * The keys as strings, sorted, for comparing key sets.
     *
     * @return list<string>
     */
    public function keySet(): array
    {
        $keys = array_map('strval', array_keys($this->entries));
        sort($keys, SORT_STRING);

        return $keys;
    }
}
