<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Data;

use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

/**
 * A parsed array or keyed header (spec §6).
 */
final readonly class Header
{
    /**
     * @param  string|null  $key  The key before the bracket segment, or null for a keyless header.
     * @param  int  $length  The declared length, or entry count for a keyed header.
     * @param  bool  $keyed  Whether this is a keyed tabular header, `[N:]`.
     * @param  list<HeaderField>|null  $fields  The field list, when the header carries one.
     * @param  string|null  $inline  Inline primitive values after the colon, when present.
     */
    public function __construct(
        public ?string $key,
        public int $length,
        public bool $keyed,
        public Delimiter $delimiter,
        public ?array $fields,
        public ?string $inline,
    ) {}

    /**
     * How many cells a row or entry row must carry: one per leaf field.
     */
    public function leafCount(): int
    {
        return $this->fields === null ? 0 : HeaderField::leaves($this->fields);
    }
}
