<?php

use RefactorCircus\ThriftToken\Facades\ThriftToken;

test('it encodes data via facade', function () {
    $result = ThriftToken::encode(['id' => 1, 'name' => 'Ada']);

    expect($result)->toBe("id: 1\nname: Ada");
});

test('it decodes data via facade', function () {
    $result = ThriftToken::decode("id: 1\nname: Ada");

    expect($result)->toBe(['id' => 1, 'name' => 'Ada']);
});

test('it compacts data via facade', function () {
    $result = ThriftToken::compact(['parent' => ['child' => 'value']]);

    expect($result)->toBe("parent:\n child: value");
});

test('it smart encodes via facade', function () {
    $result = ThriftToken::smart(['a' => ['b' => ['c' => ['d' => ['e' => 1]]]]]);

    expect($result)->toBe("a:\n b:\n  c:\n   d:\n    e: 1");
});

test('it calculates savings via facade', function () {
    $result = ThriftToken::savings(['id' => 1, 'name' => 'Ada']);

    expect($result)->toHaveKeys(['json_chars', 'toon_chars', 'saved_chars', 'saved_percent']);
});
