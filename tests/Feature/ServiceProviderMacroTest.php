<?php

use Illuminate\Http\JsonResponse;
use RefactorCircus\ThriftToken\Domains\DomainServiceProvider;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Integration\IntegrationServiceProvider;
use RefactorCircus\ThriftToken\ThriftTokenServiceProvider;

test('it registers the JsonResponse toToon macro', function () {
    expect(JsonResponse::hasMacro('toToon'))->toBeTrue();
});

test('it converts a JsonResponse to toon', function () {
    $response = new JsonResponse(['id' => 1, 'name' => 'Ada']);

    $result = $response->toToon();

    expect($result)
        ->toContain('id: 1')
        ->toContain('name: Ada');
});

test('it converts a JsonResponse with nested data to toon', function () {
    $response = new JsonResponse([
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
    ]);

    $result = $response->toToon();

    expect($result)->toContain('users[2]{id,name}:');
});

test('it accepts encoder options on JsonResponse toToon', function () {
    $response = new JsonResponse([
        'a' => ['b' => ['c' => 1]],
    ]);

    $result = $response->toToon(new EncoderOptions(indentSize: 1));

    expect($result)->toBe("a:\n b:\n  c: 1");
});

test('it encodes null JsonResponse data as empty object', function () {
    $response = new JsonResponse(null);

    $result = $response->toToon();

    expect($result)->toBe('[]');
});

test('it publishes toon config', function () {
    $provider = app()->getProvider(ThriftTokenServiceProvider::class);

    $paths = $provider::$publishGroups['thrift-token-config'] ?? [];

    expect($paths)->not->toBeEmpty();
});

test('it merges default config', function () {
    expect(config('thrift-token.indent_size'))->toBe(2)
        ->and(config('thrift-token.strict'))->toBeTrue();
});

test('it registers the domain providers', function () {
    expect(app()->getProvider(DomainServiceProvider::class))->not->toBeNull()
        ->and(app()->getProvider(IntegrationServiceProvider::class))->not->toBeNull();
});
