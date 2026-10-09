<?php

use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Integration\Pao\Plugin;
use RefactorCircus\ThriftToken\ThriftToken;

it('uses the published config defaults for encoding', function () {
    expect(ThriftToken::encode(['a' => ['b' => 1]]))->toBe("a:\n  b: 1");
});

it('reads indent_size from the toon config', function () {
    config()->set('thrift-token.indent_size', 4);

    expect(ThriftToken::encode(['a' => ['b' => 1]]))->toBe("a:\n    b: 1");
});

it('reads delimiter from the toon config', function () {
    config()->set('thrift-token.delimiter', 'pipe');

    expect(ThriftToken::encode(['tags' => ['a', 'b']]))->toBe('tags[2|]: a|b');
});

it('reads key_folding from the toon config', function () {
    config()->set('thrift-token.key_folding', 'safe');

    expect(ThriftToken::encode(['a' => ['b' => ['c' => 1]]]))->toBe('a.b.c: 1');
});

it('prefers explicit encoder options over the toon config', function () {
    config()->set('thrift-token.indent_size', 4);

    expect(ThriftToken::encode(['a' => ['b' => 1]], new EncoderOptions))->toBe("a:\n  b: 1");
});

it('reads expand_paths from the toon config', function () {
    config()->set('thrift-token.expand_paths', 'off');

    expect(ThriftToken::decode('a.b: 1'))->toBe(['a.b' => 1]);
});

it('reads strict from the toon config', function () {
    config()->set('thrift-token.strict', false);

    expect(ThriftToken::decode("x[3]:\n  - 1\n  - 2"))->toBe(['x' => [1, 2]]);
});

it('prefers explicit decoder options over the toon config', function () {
    config()->set('thrift-token.expand_paths', 'off');

    expect(ThriftToken::decode('a.b: 1', new DecoderOptions))->toBe(['a' => ['b' => 1]]);
});

it('reads pao_output from the toon config', function () {
    config()->set('thrift-token.pao_output', true);

    expect((new Plugin)->outputEnabled())->toBeTrue();

    config()->set('thrift-token.pao_output', false);

    expect((new Plugin)->outputEnabled())->toBeFalse();
});
