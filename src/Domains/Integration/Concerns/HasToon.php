<?php

namespace RefactorCircus\ThriftToken\Domains\Integration\Concerns;

use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Toon;

/**
 * Adds a `toToon()` method to Eloquent models or any class with `toArray()`.
 *
 * Usage: `$model->toToon()` or `$model->toToon(EncoderOptions::compact())`.
 */
trait HasToon
{
    public function toToon(?EncoderOptions $options = null): string
    {
        return Toon::encode($this->toArray(), $options);
    }
}
