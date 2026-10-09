# Encoding

ThriftToken provides three encoding methods and a flexible options object for controlling output.

## Methods

### `ThriftToken::encode($data, $options)`

Standard encoding with configurable options.

```php
use RefactorCircus\ThriftToken\ThriftToken;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

$toon = ThriftToken::encode($data);
$toon = ThriftToken::encode($data, new EncoderOptions(indentSize: 4));
$toon = ThriftToken::encode($data, new EncoderOptions(delimiter: Delimiter::Pipe));
```

### `ThriftToken::compact($data)`

Encodes with one space per indentation level, the smallest output for nested data.

```php
$toon = ThriftToken::compact([
    'data' => ['meta' => ['items' => ['x', 'y']]],
]);
// Output:
// data:
//  meta:
//   items[2]: x,y
```

### `ThriftToken::smart($data)`

Uses compact encoding for objects nested four or more levels deep, and default encoding otherwise.

```php
$toon = ThriftToken::smart($data); // compact for deeply nested data, default otherwise
```

## Encoder Options

All options are passed via the `EncoderOptions` value object:

```php
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;

$options = new EncoderOptions(
    indentSize: 2,
    delimiter: Delimiter::Comma,
);
```

| Option | Default | Description |
|--------|---------|-------------|
| `indentSize` | `2` | Spaces per indentation level |
| `delimiter` | `Comma` | The document delimiter, declared in every header: `Comma`, `Tab`, or `Pipe` |

### Presets

```php
EncoderOptions::compact(); // indentSize=1
```

## Forms

The form of each value follows from its shape and position, as the spec requires; there is nothing to choose:

- Arrays of scalars are inline: `tags[2]: a,b`.
- Arrays of objects with the same keys, whose values are scalars or uniform objects, are tabular, with nested objects as nested field groups: `orders[2]{id,customer{name}}:`.
- Objects of two or more uniform objects are keyed tabular: `users[2:]{age,city}:`.
- Anything else is written as list items or nested fields.

## Type Normalization

The encoder automatically normalizes common PHP types before encoding:

- Lists stay arrays; associative arrays, `stdClass` and other objects become objects. Pass a `stdClass` to keep keys such as `"0"` and `"1"` an object rather than a list
- Strings must be well-formed UTF-8; anything else throws `ToonEncodeException`

- `DateTime` / `DateTimeImmutable` / `Carbon` instances are converted to ISO 8601 strings
- Backed enums are converted to their value
- Unit enums are converted to their name
- Objects implementing `JsonSerializable` are serialized via `jsonSerialize()`
- `Stringable` objects are cast to string
- `Traversable` objects are iterated into arrays
- Other objects become objects of their public properties (an empty `stdClass` encodes as an empty object)
- Non-finite floats (`NAN`, `INF`) become `null`, `-0.0` becomes `0`, and integral floats become integers

## Token Savings

Estimate how much TOON saves compared to JSON:

```php
$stats = ThriftToken::savings($data);
// [
//     'json_chars' => 238,
//     'toon_chars' => 150,
//     'saved_chars' => 88,
//     'saved_percent' => 37.0,
// ]

// Compare with compact encoding
$stats = ThriftToken::savings($data, EncoderOptions::compact());
```
