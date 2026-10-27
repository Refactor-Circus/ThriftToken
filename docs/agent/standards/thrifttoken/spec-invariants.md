---
description: "Spec-cited quoting/number/escape/form rules; objects stay objects internally"
tags: [thrifttoken, toon-spec, encoding, decoding]
kind: architectural
---
# Spec Invariants

TOON output must match the spec exactly, and every non-obvious rule cites the spec section that requires it.

## Citing the spec

- Put the spec section and the reason in the doc comment of any non-obvious rule: §7.2 for quoting decisions, §7.1 for escapes, §2 for canonical number form.

```php
/** ... quoting per TOON spec §7.2, escaping per §7.1. Only five escapes valid. */
```

## Hard invariants

Do not simplify these:

- There are only five escape sequences: `\\ \" \n \r \t`. Other C0 controls become lowercase `\u00xx`; everything else is literal UTF-8. Decoding rejects surrogate `\u` escapes, alone or in pairs.
- Numbers are written in canonical decimal form: no scientific notation and no leading or trailing zeros, except within the spec's exponent range. When decoding, a token is a number only if it matches the JSON number grammar.
- The form comes from the value's shape and position (§9), never from a preference or option. It is inline, tabular (with nested field groups), keyed tabular or list.
- Non-strict decoding applies exactly the five recoveries in §14.4. Everything else is an error in both modes.

## Objects stay objects

- Inside the package, objects stay `ObjectValue` (encoder) and `DecodedObject` (decoder) up to the public boundary, so index-like keys never collapse into a list.
