# Stateless Helpers vs Stateful Classes

Split classes by state, not by role:

- **Static-only** — pure, no config, no state: `ValueEncoder`, `NumberEncoder`, `ValueDecoder`, `HeaderParser`, `Scanner`. All methods `static`.
- **Instance** — holds options or parse position: `ToonEncoder`, `ToonDecoder`.

Rules:
- Static helpers NEVER store config. Pass `delimiter`, `strict`, `lineNum` as method args per call.
- No singletons/globals for config.

```php
// helper: config in, result out — no state
ValueEncoder::encode($value, $delimiter, root: true);

// stateful: options in ctor, parse position in $this
new ToonDecoder($options);
```
