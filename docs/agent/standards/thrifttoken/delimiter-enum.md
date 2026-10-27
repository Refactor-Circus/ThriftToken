---
description: "Delimiter is the one option enum backed by the literal character it emits (',', \"\\t\", '|'); config names it ('comma', 'tab', 'pipe') and resolves it through the tolerant Delimiter::fromName()."
tags: [thrifttoken, enums, config, encoding]
kind: convention
---
# Delimiter Enum

Complements [[value-objects]].

`Delimiter` is backed by the character it writes into TOON, not by its config token.

## Rules

- The backing values are the literal characters `','`, `"\t"` and `'|'`, because the value is written into the output as is.
- Config uses names (`'comma'`, `'tab'`, `'pipe'`), resolved through `Delimiter::fromName()`. It accepts a name or the literal character, ignores case, and falls back to `::from()`.
- Add a `fromName`-style resolver to another option enum only when its config accepts aliases or literal characters.
- `headerSymbol()` returns `''` for `Comma`, the default, so the comma is left out of headers.

```php
Delimiter::Comma->headerSymbol();  // ''
```
