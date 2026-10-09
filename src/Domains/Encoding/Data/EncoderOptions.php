<?php

namespace RefactorCircus\ThriftToken\Domains\Encoding\Data;

use InvalidArgumentException;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

/**
 * Configuration options for the TOON encoder.
 *
 * @see EncoderOptions::compact() for a preset with the smallest indentation.
 */
readonly class EncoderOptions
{
    /**
     * @param  int  $indentSize  Spaces per indentation level (default 2).
     * @param  Delimiter  $delimiter  The document delimiter, used by every header, inline array and row.
     */
    public function __construct(
        public int $indentSize = 2,
        public Delimiter $delimiter = Delimiter::Comma,
    ) {
        if ($indentSize < 1) {
            throw new InvalidArgumentException('indentSize must be at least 1');
        }
    }

    /**
     * Preset with one space per indentation level.
     */
    public static function compact(): self
    {
        return new self(indentSize: 1);
    }
}
