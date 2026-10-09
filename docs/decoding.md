# Decoding

ThriftToken decodes TOON documents back into PHP values, following `toon-spec: 4.4`.

## Method

### `ThriftToken::decode($toon, $options)`

```php
use RefactorCircus\ThriftToken\ThriftToken;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;

$data = ThriftToken::decode($toon);

// Explicit configuration
$data = ThriftToken::decode($toon, new DecoderOptions(
    strict: true,
    indentSize: 2,
    associative: true,
));
```

## Decoder Options

| Option | Default | Description |
|--------|---------|-------------|
| `strict` | `true` | Error on every malformed input; when `false`, apply only the spec's five recoveries (below) |
| `indentSize` | `2` | Spaces per indentation level |
| `associative` | `true` | Decode objects to associative arrays; when `false`, to `stdClass` objects |

## PHP Values

- Objects decode to associative arrays, or `stdClass` objects with `associative: false`. In associative mode, an object whose keys are `"0"`, `"1"`, … is indistinguishable from a list, as with `json_decode()`; use `associative: false` when that matters.
- Empty objects always decode to `stdClass`, so they stay distinct from empty arrays. An empty document is an empty object.
- Integers decode to `int`; tokens with a fraction or exponent to `float`. An integer beyond PHP's range decodes to a `float`, and a number beyond the float range to its original string, so no value is silently clamped.
- Input must be well-formed UTF-8.
- With `associative: false`, a key starting with a NUL character throws, since a `stdClass` cannot hold it; the default associative mode keeps such keys.
- Comment lines (`# ...`) are dropped.

## Strict Mode

With `strict: true` (the default), every condition in §14 of the spec is an error: count and row-width mismatches, malformed headers and quoted strings, indentation that is not a multiple of `indentSize` or uses tabs, depth jumps, blank lines inside an array, duplicate keys, and trailing content after a root array.

With `strict: false`, exactly these five recoveries apply, and everything else is still an error:

1. Declared counts may differ from the actual number of values, items, rows or entries.
2. Duplicate keys and repeated field names resolve last-write-wins.
3. Indentation may use tabs (one level each) and any number of spaces.
4. Blank lines inside arrays are ignored.
5. A nested scope whose first line is indented deeper than expected takes that depth as its own.

Row and entry-row widths are checked in both modes.
