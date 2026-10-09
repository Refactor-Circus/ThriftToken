# Spec-Traceable Invariants

TOON output must match the spec exactly. Protect deliberate behavior from well-meaning "fixes".

Rules:
- Cite the spec section + reason on any non-obvious rule, in the doc comment.
  - e.g. quoting decisions → §7.2; escapes → §7.1; canonical number form → §2.
- Known hard invariants — don't "simplify":
  - Only 5 escape sequences: `\\ \" \n \r \t` (other C0 controls → lowercase `\u00xx`; everything else literal UTF-8). Decoding rejects surrogate `\u` escapes, lone or paired.
  - Numbers: canonical decimal, no scientific/trailing/leading zeros (except spec exponent range). Decoding: a number only when the token matches the JSON number grammar.
  - The form follows from shape and position (§9), never a preference or option: inline, tabular (with nested field groups), keyed tabular, or list.
  - Non-strict decoding applies exactly the five recoveries of §14.4; everything else errors in both modes.
- Objects stay `ObjectValue` (encoder) and `DecodedObject` (decoder) internally, so index-like keys never collapse into a list before the public boundary.

```php
/** ... quoting per TOON spec §7.2, escaping per §7.1. Only five escapes valid. */
```
