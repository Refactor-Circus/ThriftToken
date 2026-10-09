<?php

use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Decoding\Support\ToonDecoder;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;
use RefactorCircus\ThriftToken\Domains\Encoding\Support\ToonEncoder;
use RefactorCircus\ThriftToken\Exceptions\ThriftTokenException;

/*
 * The language-agnostic fixtures of the TOON specification, refreshed with
 * bin/sync-spec-fixtures.sh. Objects stay stdClass on both sides so keys such
 * as "0" are compared exactly.
 */

/**
 * @return array<string, array{0: object}>
 */
function specFixtures(string $category): array
{
    $target = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/spec/spec.json'))->version;
    $cases = [];

    foreach (glob(__DIR__."/../Fixtures/spec/$category/*.json") as $file) {
        foreach (json_decode((string) file_get_contents($file))->tests as $index => $test) {
            if (isset($test->minSpecVersion) && version_compare($test->minSpecVersion, $target, '>')) {
                continue;
            }

            $cases[basename($file, '.json')." #$index: ".$test->name] = [$test];
        }
    }

    return $cases;
}

/**
 * JSON-model equality (spec §2): ordered keys, numbers by value.
 */
function sameJsonValue(mixed $expected, mixed $actual): bool
{
    if ((is_int($expected) || is_float($expected)) && (is_int($actual) || is_float($actual))) {
        return is_int($expected) && is_int($actual) ? $expected === $actual : (float) $expected === (float) $actual;
    }

    if ($expected instanceof stdClass && $actual instanceof stdClass) {
        $expected = get_object_vars($expected);
        $actual = get_object_vars($actual);

        if (array_map('strval', array_keys($expected)) !== array_map('strval', array_keys($actual))) {
            return false;
        }

        return array_all(array_keys($expected), fn ($key): bool => sameJsonValue($expected[$key], $actual[$key]));
    }

    if (is_array($expected) && is_array($actual)) {
        return count($expected) === count($actual)
            && array_all(array_keys($expected), fn ($key): bool => sameJsonValue($expected[$key], $actual[$key] ?? null) && array_key_exists($key, $actual));
    }

    return $expected === $actual;
}

it('encodes per the spec fixtures', function (object $test) {
    $options = new EncoderOptions(
        indentSize: $test->options->indentSize ?? 2,
        delimiter: Delimiter::from($test->options->delimiter ?? ','),
    );
    $encode = fn () => (new ToonEncoder($options))->encode($test->input);

    if ($test->shouldError ?? false) {
        expect($encode)->toThrow(ThriftTokenException::class);

        return;
    }

    expect($encode())->toBe($test->expected);
})->with(specFixtures('encode'));

it('decodes per the spec fixtures', function (object $test) {
    $options = new DecoderOptions(
        strict: $test->options->strict ?? true,
        indentSize: $test->options->indentSize ?? 2,
        associative: false,
    );
    $decode = fn () => (new ToonDecoder($options))->decode($test->input);

    if ($test->shouldError ?? false) {
        expect($decode)->toThrow(ThriftTokenException::class);

        return;
    }

    $actual = $decode();

    expect(sameJsonValue($test->expected, $actual))->toBeTrue(
        'Expected '.json_encode($test->expected).' but decoded '.json_encode($actual),
    );
})->with(specFixtures('decode'));
