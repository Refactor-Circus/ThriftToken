# ThriftToken

A PHP Laravel package for encoding and decoding [TOON (Token-Oriented Object Notation)](https://toonformat.dev).

`toon-spec: 4.4` — conformance is checked against every fixture of the [specification's test suite](https://github.com/toon-format/spec/tree/main/tests) (`bin/sync-spec-fixtures.sh` refreshes them).

TOON is a line-oriented, indentation-based text format that encodes the JSON data model with explicit structure and minimal quoting. It is particularly efficient for arrays of uniform objects, reducing token count by 35-58% compared to JSON.

## Documentation

| Document | Description |
|----------|-------------|
| [Encoding](docs/encoding.md) | Encoding methods, options, presets, and type normalization |
| [Decoding](docs/decoding.md) | Decoding method, options, PHP values, and strict mode |
| [Laravel Integration](docs/laravel-integration.md) | Collection, Builder, and JsonResponse macros, HasToon trait |
| [AI Integration](docs/ai-integration.md) | Agent middleware, tool trait, MCP response macro, PAO output |
| [Format Reference](docs/format-reference.md) | TOON format overview with all structure types |
| [Configuration](docs/configuration.md) | Config options and publishing |

## Installation

The package is included via Composer path repository:

```bash
composer require refactor-circus/thrift-token
```

The service provider and facade are auto-discovered.

## Quick Start

```php
use RefactorCircus\ThriftToken\ThriftToken;

// Encode
$toon = ThriftToken::encode([
    'users' => [
        ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
        ['id' => 2, 'name' => 'Bob', 'role' => 'user'],
    ],
]);

// Output:
// users[2]{id,name,role}:
//   1,Alice,admin
//   2,Bob,user

// Decode
$data = ThriftToken::decode($toon);
```

Three encoding modes are available:

```php
ThriftToken::encode($data);          // Standard encoding
ThriftToken::compact($data);         // 1-space indentation
ThriftToken::smart($data);           // Compact for deeply nested data, standard otherwise
```

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
```

### Typical Savings

Characters saved against minified JSON, as asserted by `tests/Unit/SavingsTest.php` (the shapes are defined there):

| Data Shape | Default | Compact |
|------------|---------|---------|
| Tabular (3 rows) | 44.8% | 46.5% |
| Large tabular (10 rows) | 54.7% | 56.6% |
| Keyed objects (5 entries) | 45.8% | 47.9% |
| Nested objects in rows (3 rows) | 52.9% | 54.3% |
| Mixed (tabular + nested + lists) | 31.1% | 34.4% |
| Flat object | 16.4% | 16.4% |
| Deeply nested, single-key chains | -32.5% | -5.3% |

TOON pays off on uniform rows and objects. Deep chains of single-key objects cost indentation, and are smaller as JSON.

## Package Layout

Code is grouped into domain modules under `src/Domains/`, namespace `RefactorCircus\ThriftToken\Domains\{Domain}`:

| Domain | Contents |
|--------|----------|
| `Encoding` | `Data\EncoderOptions`, `Enums\Delimiter`, `Exceptions\ToonEncodeException`, `Support\ToonEncoder` and its helpers |
| `Decoding` | `Data\DecoderOptions`, `Data\Header`, `Exceptions\ToonDecodeException`, `Exceptions\ToonStrictModeException`, `Support\ToonDecoder` and its helpers |
| `Integration` | Laravel macros (`IntegrationServiceProvider`), `Concerns\HasToon`, laravel/ai (`Ai\*`) and laravel/pao (`Pao\*`) integrations |

`RefactorCircus\ThriftToken\ThriftToken`, `RefactorCircus\ThriftToken\Facades\ThriftToken`, `RefactorCircus\ThriftToken\ThriftTokenServiceProvider` and the base `RefactorCircus\ThriftToken\Exceptions\ThriftTokenException` stay at the package root. See [CHANGELOG](CHANGELOG.md) for the old to new class map.

## Testing

```bash
composer test       # Pest
composer phpstan    # Larastan
composer pint:test  # Pint (laravel preset)
```
