<?php

use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Exceptions\ToonEncodeException;
use RefactorCircus\ThriftToken\ThriftToken;

it('decodes objects to stdClass when associative is off, keeping index-like keys', function () {
    $result = ThriftToken::decode("\"0\": a\n\"1\": b", new DecoderOptions(associative: false));

    expect($result)->toBeInstanceOf(stdClass::class)
        ->and(json_encode($result))->toBe('{"0":"a","1":"b"}');
});

it('decodes index-like keys to a list in associative mode', function () {
    expect(ThriftToken::decode("\"0\": a\n\"1\": b"))->toBe(['a', 'b']);
});

it('encodes a stdClass as an object even when its keys look like indexes', function () {
    expect(ThriftToken::encode((object) ['0' => 'a', '1' => 'b']))->toBe("\"0\": a\n\"1\": b");
});

it('decodes empty objects to stdClass, distinct from empty arrays', function () {
    $result = ThriftToken::decode("a:\nb: []");

    expect($result['a'])->toEqual(new stdClass)
        ->and($result['b'])->toBe([]);
});

it('round-trips an empty root object through an empty document', function () {
    expect(ThriftToken::encode(new stdClass))->toBe('')
        ->and(ThriftToken::decode(''))->toEqual(new stdClass);
});

it('decodes numbers beyond the integer range as floats, and beyond the float range as strings', function () {
    expect(ThriftToken::decode('a: 123456789012345678901234567890')['a'])->toBe(1.2345678901234568E+29)
        ->and(ThriftToken::decode('a: 1e400')['a'])->toBe('1e400');
});

it('decodes fraction and exponent tokens as floats', function () {
    expect(ThriftToken::decode("a: 1.0\nb: 2e3\nc: -0"))->toBe(['a' => 1.0, 'b' => 2000.0, 'c' => 0]);
});

it('rejects ill-formed UTF-8 input', function () {
    ThriftToken::decode("a: \xC3\x28");
})->throws(ToonDecodeException::class, 'UTF-8');

it('rejects strings that are not well-formed UTF-8', function () {
    ThriftToken::encode(['a' => "\xED\xA0\x80"]);
})->throws(ToonEncodeException::class, 'UTF-8');

it('treats dotted keys as literal keys', function () {
    expect(ThriftToken::encode(['user.name' => 'Ada']))->toBe('user.name: Ada')
        ->and(ThriftToken::decode('user.name: Ada'))->toBe(['user.name' => 'Ada']);
});

it('drops comment lines when decoding', function () {
    expect(ThriftToken::decode("# users\nname: Ada\n  # nested comment\nrole: admin"))->toBe(['name' => 'Ada', 'role' => 'admin']);
});

it('keeps a key starting with NUL in associative mode', function () {
    expect(ThriftToken::decode('"\\u0000a": 1'))->toBe(["\0a" => 1]);
});

it('refuses a key starting with NUL rather than dropping it from a stdClass', function () {
    ThriftToken::decode('"\\u0000a": 1', new DecoderOptions(associative: false));
})->throws(ToonDecodeException::class, 'NUL');

it('rejects keys that are not well-formed UTF-8', function (mixed $data) {
    ThriftToken::encode($data);
})->throws(ToonEncodeException::class, 'UTF-8')->with([
    'array key' => [["\xC3" => 1]],
    'surrogate in a stdClass key' => [(object) ["\xED\xA0\x80" => 1]],
    'tabular field name' => [[['x' => 1, "\xFF" => 2], ['x' => 3, "\xFF" => 4]]],
]);

it('rejects an indent size below one', function (Closure $options) {
    $options();
})->throws(InvalidArgumentException::class)->with([
    'encoder' => [fn () => new EncoderOptions(indentSize: 0)],
    'decoder' => [fn () => new DecoderOptions(indentSize: 0)],
]);

it('writes floats with the fewest round-trip digits at power-of-two boundaries', function (float $value, string $expected) {
    expect(ThriftToken::encode($value))->toBe($expected);
})->with([
    '2^-1017' => [2.0 ** -1017, '7.120236347223045e-307'],
    '2^-140' => [2.0 ** -140, '7.174648137343064e-43'],
    'smallest subnormal' => [5e-324, '5e-324'],
    'largest double' => [1.7976931348623157e308, '1.7976931348623157e+308'],
]);
