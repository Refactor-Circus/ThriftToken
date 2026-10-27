---
description: "ThriftToken's core encodes and decodes in any PHP context; illuminate/support is the only hard dependency, and laravel/mcp, laravel/pao and laravel/ai are wired only behind class_exists in the Integration domain."
tags: [thrifttoken, dependencies, integrations, laravel]
kind: architectural
---
# ThriftToken Framework-Optional Core

Complements [[dependencies]].

Encoding and decoding must run in plain PHP scripts, in tests and outside Laravel.

## Rules

- `illuminate/support` is the only hard framework dependency. Never import another external package's class into `ThriftToken`, `Domains/Encoding` or `Domains/Decoding`.
- The optional integrations (laravel/mcp, laravel/pao, laravel/ai) are wired in the Integration domain behind `class_exists` at the boot or macro call, and never go in `require`.
- Without a booted Laravel app, `ThriftToken::laravelConfig()` returns `null` and the package defaults apply; see [[thrifttoken/config-defaults]].

```php
if (class_exists('\Laravel\Mcp\Response')) {
    McpResponse::macro('toon', fn (mixed $c) => McpResponse::text(ThriftToken::smart($c)));
}
```
