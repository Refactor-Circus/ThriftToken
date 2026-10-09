<?php

namespace RefactorCircus\ThriftToken\Domains\Integration\Ai;

use RefactorCircus\ThriftToken\ThriftToken;
use Stringable;

/**
 * Stringable wrapper that encodes data as TOON for Laravel AI tool responses.
 *
 * Uses `ThriftToken::smart()` to automatically choose the smallest encoding.
 * Returned from tools using the `EncodesToonToolResults` trait.
 */
readonly class ToonToolResponse implements Stringable
{
    private readonly string $encoded;

    public function __construct(mixed $data)
    {
        $this->encoded = ThriftToken::smart($data);
    }

    public function __toString(): string
    {
        return $this->encoded;
    }
}
