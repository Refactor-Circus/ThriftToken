<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Exceptions;

use RefactorCircus\ThriftToken\Exceptions\ThriftTokenException;
use Throwable;

/**
 * Thrown when a TOON string cannot be decoded.
 *
 * Includes the line number where the error occurred when available.
 */
class ToonDecodeException extends ThriftTokenException
{
    public readonly ?int $toonLine;

    public function __construct(
        string $message,
        ?int $toonLine = null,
        ?Throwable $previous = null,
    ) {
        $this->toonLine = $toonLine;

        $formatted = $toonLine !== null ? "Line $toonLine: $message" : $message;

        parent::__construct($formatted, 0, $previous);
    }
}
