<?php

use RefactorCircus\ThriftToken\Domains\Decoding\Data\HeaderField;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException;
use RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonStrictModeException;
use RefactorCircus\ThriftToken\Domains\Decoding\Support\HeaderParser;
use RefactorCircus\ThriftToken\Domains\Decoding\Support\Scanner;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

it('parses an inline array header', function () {
    $header = HeaderParser::parse('tags[3]: a,b,c', 1, true);

    expect($header->key)->toBe('tags')
        ->and($header->length)->toBe(3)
        ->and($header->keyed)->toBeFalse()
        ->and($header->delimiter)->toBe(Delimiter::Comma)
        ->and($header->fields)->toBeNull()
        ->and($header->inline)->toBe('a,b,c');
});

it('parses a tabular header with fields', function () {
    $header = HeaderParser::parse('items[2]{id,name}:', 1, true);

    expect($header->key)->toBe('items')
        ->and(array_map(fn (HeaderField $field) => $field->name, $header->fields))->toBe(['id', 'name'])
        ->and($header->leafCount())->toBe(2)
        ->and($header->inline)->toBeNull();
});

it('parses nested field groups', function () {
    $header = HeaderParser::parse('orders[1]{id,customer{name,address{city}}}:', 1, true);

    expect($header->fields[1]->name)->toBe('customer')
        ->and($header->fields[1]->children[1]->children[0]->name)->toBe('city')
        ->and($header->leafCount())->toBe(3);
});

it('parses a keyed header', function () {
    $header = HeaderParser::parse('users[2:|]{age|city}:', 1, true);

    expect($header->keyed)->toBeTrue()
        ->and($header->length)->toBe(2)
        ->and($header->delimiter)->toBe(Delimiter::Pipe);
});

it('parses a keyless root header', function () {
    $header = HeaderParser::parse('[3]: a,b,c', 1, true);

    expect($header->key)->toBeNull()
        ->and($header->inline)->toBe('a,b,c');
});

it('parses the declared delimiter', function (string $line, Delimiter $delimiter) {
    expect(HeaderParser::parse($line, 1, true)->delimiter)->toBe($delimiter);
})->with([
    'comma by default' => ['data[2]: a,b', Delimiter::Comma],
    'pipe' => ['data[2|]: a|b', Delimiter::Pipe],
    'tab' => ["data[2\t]: a\tb", Delimiter::Tab],
]);

it('unescapes quoted keys and field names', function () {
    $header = HeaderParser::parse('"my key"[1]{"first name",id}:', 1, true);

    expect($header->key)->toBe('my key')
        ->and($header->fields[0]->name)->toBe('first name');
});

it('accepts inline values without a space after the colon', function () {
    expect(HeaderParser::parse('tags[2]:a,b', 1, true)->inline)->toBe('a,b');
});

it('rejects malformed headers', function (string $line) {
    HeaderParser::parse($line, 1, true);
})->throws(ToonDecodeException::class)->with([
    'non-numeric length' => ['items[abc]:'],
    'leading zero length' => ['items[03]:'],
    'unclosed bracket' => ['a[1: x'],
    'no colon after the field list' => ['a[2]{x}'],
    'text between bracket and brace' => ['items[2]x{a}:'],
    'space before the colon' => ['items[2] :'],
    'space between key and bracket' => ['foo [2]: a,b'],
    'unclosed brace' => ['items[2]{a,b:'],
    'empty field list' => ['items[2]{}:'],
    'empty field entry' => ['items[2]{a,}:'],
    'nameless nested group' => ['items[2]{a,{b}}:'],
    'keyed header without fields' => ['users[2:]:'],
    'content after a field list' => ['items[2]{a,b}: 1,2'],
    'foreign delimiter in the field list' => ['items[2|]{a,b}:'],
]);

it('rejects repeated field names in strict mode', function () {
    HeaderParser::parse('items[1]{a,a}:', 1, true);
})->throws(ToonStrictModeException::class);

it('keeps repeated field names in non-strict mode', function () {
    expect(HeaderParser::parse('items[1]{a,a}:', 1, false)->leafCount())->toBe(2);
});

it('recognises header lines by an unquoted bracket before the first colon', function (string $line, bool $isHeader) {
    expect(HeaderParser::isHeader($line))->toBe($isHeader);
})->with([
    'array header' => ['tags[2]: a,b', true],
    'quoted key holding a colon' => ['"a:b"[2]: 1,2', true],
    'colon before the bracket' => ['a:b[2]: x', false],
    'no colon at all' => ['items[2]', false],
]);

it('splits on the delimiter outside quotes, keeping empty tokens', function (string $content, string $delimiter, array $tokens) {
    expect(Scanner::split($content, $delimiter))->toBe($tokens);
})->with([
    'quoted delimiter' => ['a,"b,c",d', ',', ['a', '"b,c"', 'd']],
    'pipes' => ['a|b|c', '|', ['a', 'b', 'c']],
    'escaped quote inside quotes' => ['"a\"b",c', ',', ['"a\"b"', 'c']],
    'empty tokens' => [',a,', ',', ['', 'a', '']],
]);
