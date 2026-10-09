<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Data;

use RefactorCircus\ThriftToken\Domains\Decoding\Enums\PathExpansion;

/**
 * Configuration options for the TOON decoder.
 */
readonly class DecoderOptions
{
    /**
     * @param  bool  $strict  Enforce strict mode validation (counts, indentation, escapes).
     * @param  int|null  $indentSize  Spaces per level, or null to auto-detect from first indented line.
     * @param  PathExpansion  $expandPaths  How to handle dotted keys (Off, Safe, or Auto).
     */
    public function __construct(
        public bool $strict = true,
        public ?int $indentSize = null,
        public PathExpansion $expandPaths = PathExpansion::Auto,
    ) {}
}
