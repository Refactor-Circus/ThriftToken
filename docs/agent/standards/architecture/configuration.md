---
description: 'ThriftToken override of the global configuration standard: one self-documenting config/thrift-token.php, env() only in config, caller options win, cast plus hard default on every read, safe defaults, opt-in flags false and boolean-coerced; framework-free exception: with no container or no bound config, an opt-in flag may be read from the environment with getenv()/$_ENV (never env()) as its one fallback.'
tags:
  - architecture
  - configuration
  - config
  - env
  - opt-in
  - framework-optional
kind: architectural
overrides: refactor-circus/standards
reviewed_hash: 189202ea52eb
---
# Configuration

This replaces the global `architecture/configuration` standard for ThriftToken. It keeps the global rules and adds one exception, because ThriftToken also runs outside Laravel; see [[thrifttoken/framework-optional]].

ThriftToken has one config file, `config/thrift-token.php`, that documents itself, defaults to the safe value, and is the only place that reads `env()`.

## One file, documented

- Domains read their keys from the package config and never ship their own file; see [[domain-modules]].
- The file is user-facing documentation. Every section has a banner comment that states what it controls, the values or modes it accepts and what each does, and its security implications.
- A section that exposes routes names the authentication middleware to add before production. This is mandatory.
- A new key without a documented section is an incomplete change.

```php
/*
|--------------------------------------------------------------------------
| PAO Test Output Override
|--------------------------------------------------------------------------
|
| When enabled, test output from PAO (laravel/pao) will be encoded as
| TOON instead of JSON. Only takes effect when PAO is installed.
|
| Set the THRIFT_TOKEN_PAO_OUTPUT environment variable to "true" to enable.
|
*/
'pao_output' => env('THRIFT_TOKEN_PAO_OUTPUT', false),
```

## Reading config

- `env()` appears only in the config file, as the default of a key. Code reads `config('thrift-token.key')`; `env()` returns null once config is cached.
- Config supplies defaults; options a caller passes always win; see [[public-api]]. A caller's `EncoderOptions`/`DecoderOptions` are used exactly as given; see [[thrifttoken/config-defaults]].
- Every read casts and carries a hard default for when the key is absent: `(int) ($config['indent_size'] ?? 2)`. A published config may be older than the package, and outside Laravel there is no config at all.

## Framework-free fallback

ThriftToken works without Laravel, so a flag that must also work in that context may read the environment directly. This is the only place code outside `config/thrift-token.php` reads the environment.

- It applies only when there is no config to read: `Illuminate\Container\Container` does not exist, or `Container::getInstance()` has no `config` binding. When `config` is bound, read the config key and nothing else.
- It applies only to opt-in flags (booleans that default to `false`), not to other settings, which take their package defaults when there is no config.
- The direct read uses `getenv('THRIFT_TOKEN_...')` and then `$_ENV['THRIFT_TOKEN_...']`, with `'false'` as the default. Never `env()`: it is a Laravel helper, is unavailable or null outside a booted app, and stays banned in code.
- The variable name is the same one the config file passes to `env()` for that key, and the env read is also the default of the config read, so both paths agree.
- The result is always coerced with `filter_var(..., FILTER_VALIDATE_BOOLEAN)`.
- Never assume the container is booted; check before every read.

```php
public function outputEnabled(): bool
{
    if (class_exists(Container::class)) {
        $container = Container::getInstance();

        if ($container->bound('config')) {
            return filter_var($container->make('config')->get('thrift-token.pao_output', $this->envFlag()), FILTER_VALIDATE_BOOLEAN);
        }
    }

    return $this->envFlag();
}

private function envFlag(): bool
{
    return filter_var(getenv('THRIFT_TOKEN_PAO_OUTPUT') ?: ($_ENV['THRIFT_TOKEN_PAO_OUTPUT'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);
}
```

`Domains/Integration/Pao/Plugin` is the reference: Pest loads it in CLI runs where Laravel may not be booted.

## Safe defaults

- Security settings default to the safe value and are switched off explicitly (`$config['verify_ssl'] ?? true`), never switched on.
- Optional behaviour is an opt-in flag that defaults to `false`, with an env default, coerced with `filter_var(..., FILTER_VALIDATE_BOOLEAN)`, because env strings like `"false"` are truthy.
- A missing or incomplete named entry throws right away and names the entry. Never fall back to something silently; see [[exceptions]]. The framework-free fallback above is a documented default, not a silent one.

## Routes and tables

ThriftToken has no routes or tables today. If it adds them:

- Routes are switched and configured from `thrift-token.routes` (`enabled`, `prefix`, `middleware`), and that section's banner names the authentication middleware to add.
- Table names are configurable through a `thrift-token.tables` map of key to default name. Models resolve `getTable()` from it and migrations use the same keys, never a literal table name; see [[models]] and [[migrations]]. A published config that lists only some tables keeps the defaults for the rest.
