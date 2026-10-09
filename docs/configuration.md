# Configuration

ThriftToken ships with sensible defaults but every option is configurable via the publishable config file.

## Publishing the Config

```bash
php artisan vendor:publish --tag=thrift-token-config
```

This creates `config/thrift-token.php` in your application.

## Options

```php
// config/thrift-token.php
return [
    'indent_size' => 2,
    'delimiter' => 'comma',
    'strict' => true,
    'pao_output' => env('THRIFT_TOKEN_PAO_OUTPUT', false),
];
```

### Encoding and Decoding Options

| Key | Default | Values | Description |
|-----|---------|--------|-------------|
| `indent_size` | `2` | Any positive integer | Spaces per indentation level, for encoding and decoding |
| `delimiter` | `'comma'` | `'comma'`, `'tab'`, `'pipe'` | The document delimiter used when encoding |
| `strict` | `true` | `true`, `false` | Whether decoding errors on every malformed input |

### PAO Integration

| Key | Default | Values | Description |
|-----|---------|--------|-------------|
| `pao_output` | `false` | `true`, `false` | Encode PAO test output as TOON when an AI agent is detected |

Set via the `THRIFT_TOKEN_PAO_OUTPUT` environment variable or directly in the config file.

## Runtime Override

Config values provide defaults. You can always override per-call by passing an options object:

```php
use RefactorCircus\ThriftToken\ThriftToken;
use RefactorCircus\ThriftToken\Domains\Encoding\Data\EncoderOptions;
use RefactorCircus\ThriftToken\Domains\Encoding\Enums\Delimiter;

// Uses config defaults
ThriftToken::encode($data);

// Overrides delimiter for this call only
ThriftToken::encode($data, new EncoderOptions(delimiter: Delimiter::Pipe));
```
