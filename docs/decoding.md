# Decoding

ThriftToken decodes TOON-formatted strings back into PHP arrays with automatic detection of indent size and key expansion.

## Method

### `ThriftToken::decode($toon, $options)`

```php
use RefactorCircus\ThriftToken\ThriftToken;
use RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions;
use RefactorCircus\ThriftToken\Domains\Decoding\Enums\PathExpansion;

// Auto-detect everything (default)
$data = ThriftToken::decode($toon);

// Explicit configuration
$data = ThriftToken::decode($toon, new DecoderOptions(
    strict: true,
    indentSize: 2,
    expandPaths: PathExpansion::Off,
));
```

## Decoder Options

| Option | Default | Description |
|--------|---------|-------------|
| `strict` | `true` | Enforce count mismatches, indentation, escapes, blank lines in arrays |
| `indentSize` | `null` | Spaces per level. `null` auto-detects from the first indented line |
| `expandPaths` | `Auto` | `Off`: dotted keys are literal. `Safe`: always expand. `Auto`: expand when dotted keys detected |

## Auto-Detection

When `indentSize` is `null` (the default), the decoder examines the first indented line in the input to determine the indent size. This works reliably for any consistent indentation (1, 2, 4 spaces, etc.).

When `expandPaths` is set to `Auto`, the decoder checks whether any key (at any depth, including inside list items) is a dotted path starting with an identifier segment. If one is found, dotted keys are expanded into nested structures:

```php
$toon = "a.b.c: 1";

// Auto mode (default) — expands because dotted key detected
ThriftToken::decode($toon);
// ['a' => ['b' => ['c' => 1]]]

// Off mode — treats dot as literal key character
ThriftToken::decode($toon, new DecoderOptions(expandPaths: PathExpansion::Off));
// ['a.b.c' => 1]
```

## Strict Mode

With `strict: true` (the default), the decoder validates:

- **Count mismatches** — the declared count in array headers (e.g., `[3]`) must match the actual number of elements
- **Indentation consistency** — all lines within a block must use the same indent level
- **Escape sequences** — only valid escape sequences are accepted
- **Blank lines in arrays** — blank lines within tabular or list arrays are rejected

Set `strict: false` to relax these checks when working with hand-edited or potentially malformed TOON input.
