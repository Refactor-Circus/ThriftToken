---
description: "Uniform toToon(?EncoderOptions) surface across macros and HasToon, all delegate to encode"
tags: [thrifttoken, public-api, macros, integrations]
kind: architectural
---
# Uniform toToon() Surface

Every type that can encode itself exposes the same method, `toToon(?EncoderOptions $options = null): string`.

## Where it is registered

- `Collection`, `Builder` and `JsonResponse`: macros in `IntegrationServiceProvider::boot()` (Integration domain).
- Any class with `toArray()`: the `HasToon` trait.

## Rules

- Each implementation converts its source to an array and then calls `ThriftToken::encode($data, $options)`.
- Macros and the trait hold no encoding logic.
- A new `toToon` keeps the same signature.

```php
Collection::macro('toToon', fn (?EncoderOptions $options = null) => ThriftToken::encode($this->all(), $options));
Builder::macro('toToon', fn (?EncoderOptions $options = null) => ThriftToken::encode($this->get()->toArray(), $options));
```
