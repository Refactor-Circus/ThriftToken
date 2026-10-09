# Changelog

## [Unreleased](https://github.com/Refactor-Circus/ThriftToken/commits/main)

### Breaking

- Moved to the Refactor Circus organisation: the package is now `refactor-circus/thrift-token` with the PHP namespace `RefactorCircus\ThriftToken` (it was `jayi/toon` and `JayI\Toon`). Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.
- Renamed the package's own names from Toon to ThriftToken: the `Toon` class and facade are `ThriftToken` (`ThriftToken::encode()`), `ToonServiceProvider` is `ThriftTokenServiceProvider`, `ToonException` is `ThriftTokenException`, the config is `config/thrift-token.php` with `thrift-token.*` keys and the `thrift-token-config` publish tag, and `TOON_PAO_OUTPUT` is `THRIFT_TOKEN_PAO_OUTPUT`. Names for the TOON format itself stay: `ToonEncoder`, `ToonDecoder`, the `Toon*Exception` format errors, `HasToon`, `ToonMiddleware`, `ToonToolResponse`, and the `toToon()`/`toon()` macros. Republish the config as `config/thrift-token.php`.
- Implements TOON specification 4.4 (it was 3.3), checked against every fixture of the specification's test suite. Output and accepted input change accordingly:
  - Key folding and path expansion are gone, as in the spec: `EncoderOptions::$keyFolding` and `$flattenDepth`, `DecoderOptions::$expandPaths`, the `KeyFolding` and `PathExpansion` enums, `KeyFolder`, `PathExpander`, and the `key_folding`, `flatten_depth` and `expand_paths` config keys are removed. Dotted keys are always literal and written unquoted. `EncoderOptions::compact()` and `ThriftToken::compact()` now only use one-space indentation, and `smart()` picks compact for objects nested four or more levels deep. To read 3.x output written with key folding, decode it with a 3.x release and re-encode.
  - Arrays of objects whose columns hold uniform objects encode in tabular form with nested field groups (`orders[2]{id,customer{name,country}}:`), and objects of two or more uniform objects in keyed tabular form (`users[2:]{age,city}:`). Strict 3.x decoders reject both.
  - Decoding drops `#` comment lines; strings starting with `#` and number-like strings with a leading `+` are quoted.
  - Only tokens matching the JSON number grammar decode as numbers, so `+1`, `.5` and `NaN` are strings.
  - `DecoderOptions::$indentSize` defaults to 2, as in the spec, instead of auto-detecting.
  - Strict and non-strict decoding follow spec §14: non-strict mode applies only the five recoveries of §14.4, so row-width mismatches, over-indented lines, lines without a colon and trailing content now error in both modes. Trailing spaces are stripped rather than rejected.
  - Surrogate `\u` escapes are rejected even in pairs; characters above U+FFFF must be literal UTF-8. Control characters other than `\n`, `\r` and `\t` are escaped as lowercase `\u00xx`, and DEL is written literally.
  - Input must be well-formed UTF-8, and so must strings and keys passed to the encoder. An `indentSize` below 1 throws `InvalidArgumentException`.
  - Floats are written with PHP's shortest round-trip digits, which fixes an extra digit on some powers of two outside the plain-decimal range.
  - `HeaderParser::parse()` returns a `Header` value object and throws on malformed headers instead of returning null; `ValueEncoder::encode()` takes the document delimiter only.

The package now uses the domain-module layout (`src/Domains/{Encoding,Decoding,Integration}`). Classes moved without aliases; update imports:

| Old | New |
|-----|-----|
| `RefactorCircus\ThriftToken\Encoding\EncoderOptions` | `RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions` |
| `RefactorCircus\ThriftToken\Encoding\ToonEncoder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\ToonEncoder` |
| `RefactorCircus\ThriftToken\Encoding\ValueEncoder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\ValueEncoder` |
| `RefactorCircus\ThriftToken\Encoding\NumberEncoder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\NumberEncoder` |
| `RefactorCircus\ThriftToken\Enums\Delimiter` | `RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter` |
| `RefactorCircus\ThriftToken\Exceptions\ToonEncodeException` | `RefactorCircus\ThriftToken\Domains\Encoding\Exceptions\ToonEncodeException` |
| `RefactorCircus\ThriftToken\Decoding\DecoderOptions` | `RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions` |
| `RefactorCircus\ThriftToken\Decoding\ToonDecoder` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\ToonDecoder` |
| `RefactorCircus\ThriftToken\Decoding\ValueDecoder` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\ValueDecoder` |
| `RefactorCircus\ThriftToken\Decoding\HeaderParser` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\HeaderParser` |
| `RefactorCircus\ThriftToken\Exceptions\ToonDecodeException` | `RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException` |
| `RefactorCircus\ThriftToken\Exceptions\ToonStrictModeException` | `RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonStrictModeException` |
| `RefactorCircus\ThriftToken\Traits\HasToon` | `RefactorCircus\ThriftToken\Domains\Integration\Concerns\HasToon` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Ai\EncodesToonToolResults` | `RefactorCircus\ThriftToken\Domains\Integration\Ai\EncodesToonToolResults` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Ai\ToonMiddleware` | `RefactorCircus\ThriftToken\Domains\Integration\Ai\ToonMiddleware` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Ai\ToonToolResponse` | `RefactorCircus\ThriftToken\Domains\Integration\Ai\ToonToolResponse` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Pao\Plugin` | `RefactorCircus\ThriftToken\Domains\Integration\Pao\Plugin` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Pao\ToonOutputFilter` | `RefactorCircus\ThriftToken\Domains\Integration\Pao\ToonOutputFilter` |

The Laravel macros are now registered by `RefactorCircus\ThriftToken\Domains\Integration\IntegrationServiceProvider`, which `ThriftTokenServiceProvider` registers through `RefactorCircus\ThriftToken\Domains\DomainServiceProvider`. The macro names are unchanged. The Pest plugin entry in `extra.pest.plugins` now points at `RefactorCircus\ThriftToken\Domains\Integration\Pao\Plugin`.

### Added

- `DecoderOptions::$associative`: set it to `false` to decode objects to `stdClass`, keeping keys such as `"0"` distinct from list indexes. The encoder treats every `stdClass` as an object for the same reason.
- `bin/sync-spec-fixtures.sh` refreshes the vendored specification fixtures in `tests/Fixtures/spec`.

### Changed

- Requires PHP 8.5 or later.
