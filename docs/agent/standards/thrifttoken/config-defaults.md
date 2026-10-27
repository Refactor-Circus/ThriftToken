---
description: "ThriftToken config keys feed EncoderOptions/DecoderOptions defaults via ThriftToken::laravelConfig(), which returns null outside a booted Laravel app; every new key needs a hard default for that null path."
tags: [thrifttoken, config, options, defaults]
kind: convention
---
# ThriftToken Config Defaults

Complements [[configuration]].

`config/thrift-token.php` fills in the default `EncoderOptions`/`DecoderOptions`, and it must also work when there is no config at all.

## Reading config

- Code reads config through `ThriftToken::laravelConfig()`. It returns `null` when no container is set or no `config` is bound, so the package defaults apply.
- When a caller passes `EncoderOptions`/`DecoderOptions`, they are used exactly as given and config is not read.

## Adding a key

- Map the key into the constructor of `EncoderOptions` or `DecoderOptions`.
- Give it a hard default for the null-config path, which is any use outside Laravel.

```php
indentSize: (int) ($config['indent_size'] ?? 2),
```
