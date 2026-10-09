<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Data;

/**
 * Configuration options for the TOON decoder.
 */
readonly class DecoderOptions
{
    /**
     * @param  bool  $strict  Error on every condition of spec §14; when false, apply only the recoveries of §14.4.
     * @param  int  $indentSize  Spaces per indentation level (default 2).
     * @param  bool  $associative  Decode objects to associative arrays (default) or, when false, to stdClass objects, which keep keys like "0" distinct from list indexes.
     */
    public function __construct(
        public bool $strict = true,
        public int $indentSize = 2,
        public bool $associative = true,
    ) {}
}
