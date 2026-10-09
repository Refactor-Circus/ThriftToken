<?php

namespace RefactorCircus\ThriftToken;

use Illuminate\Container\Container;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Decoding\Support\ToonDecoder;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;
use RefactorCircus\ThriftToken\Domains\Encoding\Support\ToonEncoder;

/**
 * TOON (Token-Oriented Object Notation) encoder and decoder.
 *
 * Provides static methods for encoding PHP data to TOON format,
 * decoding TOON strings back to PHP, and estimating token savings.
 *
 * @see https://toonformat.dev
 */
class ThriftToken
{
    /**
     * Encode data to TOON format.
     *
     * When no options are given, defaults are read from the `thrift-token` config
     * inside Laravel and fall back to the package defaults elsewhere.
     */
    public static function encode(mixed $data, ?EncoderOptions $options = null): string
    {
        $encoder = new ToonEncoder($options ?? self::defaultEncoderOptions());

        return $encoder->encode($data);
    }

    /**
     * Encode data with one space per indentation level.
     */
    public static function compact(mixed $data): string
    {
        return static::encode($data, EncoderOptions::compact());
    }

    /**
     * Encode data, choosing compact indentation for deeply nested objects,
     * where it saves the most.
     */
    public static function smart(mixed $data): string
    {
        return is_array($data) && ! array_is_list($data) && self::depth($data) >= 4
            ? static::compact($data)
            : static::encode($data);
    }

    /**
     * How deeply objects nest inside an object.
     *
     * @param  array<array-key, mixed>  $data
     */
    private static function depth(array $data): int
    {
        $depth = 0;

        foreach ($data as $value) {
            if (is_array($value) && ! array_is_list($value)) {
                $depth = max($depth, 1 + self::depth($value));
            }
        }

        return $depth;
    }

    /**
     * Decode a TOON string back to PHP data.
     *
     * When no options are given, defaults are read from the `thrift-token`
     * config inside Laravel and fall back to the package defaults elsewhere.
     *
     * An empty document decodes to an empty object (a stdClass), which is
     * what an empty root object encodes to.
     */
    public static function decode(string $toon, ?DecoderOptions $options = null): mixed
    {
        $decoder = new ToonDecoder($options ?? self::defaultDecoderOptions());

        return $decoder->decode($toon);
    }

    /**
     * Estimate the percentage of tokens saved by encoding as TOON instead of JSON.
     *
     * Uses character count as a proxy for token count (~4 chars/token).
     *
     * @return array{json_chars: int, toon_chars: int, saved_chars: int, saved_percent: float}
     *
     * @throws \JsonException When the data cannot be encoded as JSON.
     */
    public static function savings(mixed $data, ?EncoderOptions $options = null): array
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $toon = static::encode($data, $options);

        $jsonLen = strlen($json);
        $toonLen = strlen($toon);
        $saved = $jsonLen - $toonLen;

        return [
            'json_chars' => $jsonLen,
            'toon_chars' => $toonLen,
            'saved_chars' => $saved,
            'saved_percent' => round(($saved / $jsonLen) * 100, 1),
        ];
    }

    /**
     * Build encoder defaults from the Laravel `thrift-token` config when available.
     */
    private static function defaultEncoderOptions(): EncoderOptions
    {
        $config = self::laravelConfig();

        if ($config === null) {
            return new EncoderOptions;
        }

        return new EncoderOptions(
            indentSize: (int) ($config['indent_size'] ?? 2),
            delimiter: Delimiter::fromName((string) ($config['delimiter'] ?? 'comma')),
        );
    }

    /**
     * Build decoder defaults from the Laravel `thrift-token` config when available.
     */
    private static function defaultDecoderOptions(): DecoderOptions
    {
        $config = self::laravelConfig();

        if ($config === null) {
            return new DecoderOptions;
        }

        return new DecoderOptions(
            strict: (bool) ($config['strict'] ?? true),
            indentSize: (int) ($config['indent_size'] ?? 2),
        );
    }

    /**
     * Read the `thrift-token` config array from the Laravel container, or null when
     * running outside a booted Laravel application.
     *
     * @return array<string, mixed>|null
     */
    private static function laravelConfig(): ?array
    {
        if (! class_exists(Container::class)) {
            return null;
        }

        $container = Container::getInstance();

        if (! $container->bound('config')) {
            return null;
        }

        $config = $container->make('config')->get('thrift-token');

        return is_array($config) ? $config : null;
    }
}
