---
description: "Encoder normalizes input to primitives in one pass before emitting"
tags: [thrifttoken, encoding, normalization, types]
kind: architectural
---
# Normalize Before Encoding

`ToonEncoder::encode()` runs one `normalize()` pass before it writes any TOON, so the code after it only ever sees primitives.

## The invariant

After `normalize()`, the code that follows sees only `null`, `bool`, `int`, `float`, `string` and `array` (a list or a map). All type conversion happens in `normalize()`, which keeps it in one place to reason about and test.

## Mappings

- `DateTimeInterface` → ISO-8601 string (`format('c')`)
- `BackedEnum` → `->value`; `UnitEnum` → `->name`
- `JsonSerializable` → `jsonSerialize()`, recursed
- `Stringable` → `(string)`
- `Traversable` and other objects → array, recursed
- Non-finite float (NaN/Inf) → `null`; `-0.0` → `0`; a float with no fractional part → `int`

## Rules

- Handling for a new type goes in `normalize()`, never in `encodeValue()` or another emit path. Converting types later breaks the primitives-only invariant.
- Any new float path must turn NaN and Infinity into `null`, or the output is not valid TOON.
