<?php

namespace RefactorCircus\ThriftToken\Facades;

use Illuminate\Support\Facades\Facade;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;

/**
 * @method static string encode(mixed $data, ?EncoderOptions $options = null) Encode data to TOON format.
 * @method static string compact(mixed $data) Encode with one space per indentation level.
 * @method static string smart(mixed $data) Encode, using compact indentation for deeply nested objects.
 * @method static mixed decode(string $toon, ?DecoderOptions $options = null) Decode a TOON string to PHP data.
 * @method static array savings(mixed $data, ?EncoderOptions $options = null) Estimate token savings vs JSON.
 *
 * @see \RefactorCircus\ThriftToken\ThriftToken
 */
class ThriftToken extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\ThriftToken\ThriftToken::class;
    }
}
