---
description: "decode(encode(x))===x is the primary check; add a roundtrip case per new type"
tags: [thrifttoken, testing, roundtrip, pest]
kind: convention
---
# Roundtrip Contract

The main guarantee is that no data is lost, so the central assertion is that `decode(encode($x))` returns exactly `$x` in strict mode.

```php
expect($decoder->decode($encoder->encode($data)))->toBe($data);   // strict mode
```

## Rules

- A roundtrip proves nothing is lost for any structure. Exact TOON string assertions are brittle, so use them only for format details (spacing, header form), not as the main coverage.
- The local `roundtrip()` helper wraps encode and then decode. Curated `->with()` datasets feed it difficult structures.
- For a newly supported type or structure, add a strict roundtrip dataset row first, then any format-specific tests.
