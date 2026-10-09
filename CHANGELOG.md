# Changelog

## [Unreleased](https://github.com/Refactor-Circus/ThriftToken/commits/main)

### Breaking

- Moved to the Refactor Circus organisation: the package is now `refactor-circus/thrift-token` with the PHP namespace `RefactorCircus\ThriftToken` (it was `jayi/toon` and `JayI\Toon`). Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.
The package now uses the domain-module layout (`src/Domains/{Encoding,Decoding,Integration}`). Classes moved without aliases; update imports:

| Old | New |
|-----|-----|
| `RefactorCircus\ThriftToken\Encoding\EncoderOptions` | `RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions` |
| `RefactorCircus\ThriftToken\Encoding\ToonEncoder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\ToonEncoder` |
| `RefactorCircus\ThriftToken\Encoding\ValueEncoder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\ValueEncoder` |
| `RefactorCircus\ThriftToken\Encoding\KeyFolder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\KeyFolder` |
| `RefactorCircus\ThriftToken\Encoding\NumberEncoder` | `RefactorCircus\ThriftToken\Domains\Encoding\Support\NumberEncoder` |
| `RefactorCircus\ThriftToken\Enums\Delimiter` | `RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter` |
| `RefactorCircus\ThriftToken\Enums\KeyFolding` | `RefactorCircus\ThriftToken\Domains\Encoding\Enums\KeyFolding` |
| `RefactorCircus\ThriftToken\Exceptions\ToonEncodeException` | `RefactorCircus\ThriftToken\Domains\Encoding\Exceptions\ToonEncodeException` |
| `RefactorCircus\ThriftToken\Decoding\DecoderOptions` | `RefactorCircus\ThriftToken\Domains\Decoding\Data\DecoderOptions` |
| `RefactorCircus\ThriftToken\Decoding\ToonDecoder` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\ToonDecoder` |
| `RefactorCircus\ThriftToken\Decoding\ValueDecoder` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\ValueDecoder` |
| `RefactorCircus\ThriftToken\Decoding\HeaderParser` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\HeaderParser` |
| `RefactorCircus\ThriftToken\Decoding\PathExpander` | `RefactorCircus\ThriftToken\Domains\Decoding\Support\PathExpander` |
| `RefactorCircus\ThriftToken\Enums\PathExpansion` | `RefactorCircus\ThriftToken\Domains\Decoding\Enums\PathExpansion` |
| `RefactorCircus\ThriftToken\Exceptions\ToonDecodeException` | `RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonDecodeException` |
| `RefactorCircus\ThriftToken\Exceptions\ToonStrictModeException` | `RefactorCircus\ThriftToken\Domains\Decoding\Exceptions\ToonStrictModeException` |
| `RefactorCircus\ThriftToken\Traits\HasToon` | `RefactorCircus\ThriftToken\Domains\Integration\Concerns\HasToon` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Ai\EncodesToonToolResults` | `RefactorCircus\ThriftToken\Domains\Integration\Ai\EncodesToonToolResults` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Ai\ToonMiddleware` | `RefactorCircus\ThriftToken\Domains\Integration\Ai\ToonMiddleware` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Ai\ToonToolResponse` | `RefactorCircus\ThriftToken\Domains\Integration\Ai\ToonToolResponse` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Pao\Plugin` | `RefactorCircus\ThriftToken\Domains\Integration\Pao\Plugin` |
| `RefactorCircus\ThriftToken\Overrides\Laravel\Pao\ToonOutputFilter` | `RefactorCircus\ThriftToken\Domains\Integration\Pao\ToonOutputFilter` |

The Laravel macros are now registered by `RefactorCircus\ThriftToken\Domains\Integration\IntegrationServiceProvider`, which `ToonServiceProvider` registers through `RefactorCircus\ThriftToken\Domains\DomainServiceProvider`. `ToonServiceProvider`, `Toon`, the `Toon` facade, `ToonException`, the `toon` config, the `toon-config` publish tag and the macro names are unchanged. The Pest plugin entry in `extra.pest.plugins` now points at `RefactorCircus\ThriftToken\Domains\Integration\Pao\Plugin`.
