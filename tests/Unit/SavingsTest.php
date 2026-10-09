<?php

use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\ThriftToken;

/*
 * The figures in the README's "Typical Savings" table. Change a shape or the
 * encoder and this test says which README numbers to update.
 */

function savingsRow(int $id): array
{
    return ['id' => $id, 'name' => "User $id", 'role' => $id % 2 ? 'admin' : 'user', 'active' => true];
}

dataset('savings shapes', [
    'tabular (3 rows)' => [fn () => ['users' => array_map(savingsRow(...), range(1, 3))], 44.8, 46.5],
    'large tabular (10 rows)' => [fn () => ['users' => array_map(savingsRow(...), range(1, 10))], 54.7, 56.6],
    'keyed objects (5 entries)' => [fn () => ['users' => array_combine(
        ['ada', 'bob', 'cy', 'di', 'ed'],
        array_map(fn (int $i) => ['age' => 20 + $i, 'city' => 'Paris', 'active' => true], range(1, 5)),
    )], 45.8, 47.9],
    'nested objects in rows (3 rows)' => [fn () => ['orders' => array_map(
        fn (int $i) => ['id' => $i, 'total' => $i * 10.5, 'customer' => ['name' => "User $i", 'country' => 'UK']],
        range(1, 3),
    )], 52.9, 54.3],
    'mixed (tabular + nested + lists)' => [fn () => [
        'meta' => ['page' => 1, 'total' => 3],
        'tags' => ['a', 'b'],
        'items' => array_map(savingsRow(...), range(1, 3)),
        'notes' => [1, ['x' => 1], 'y'],
    ], 31.1, 34.4],
    'flat object' => [fn () => ['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com', 'active' => true], 16.4, 16.4],
    'deeply nested, single-key chains' => [fn () => ['app' => ['config' => ['database' => ['connections' => ['mysql' => [
        'host' => 'localhost', 'port' => 3306, 'options' => ['timeout' => 5],
    ]]]]]], -32.5, -5.3],
]);

it('saves what the README says', function (array $data, float $default, float $compact) {
    expect(ThriftToken::savings($data)['saved_percent'])->toBe($default)
        ->and(ThriftToken::savings($data, EncoderOptions::compact())['saved_percent'])->toBe($compact);
})->with('savings shapes');
