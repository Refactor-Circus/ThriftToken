---
description: "Convention — it() over test()/describe, strict toBe() over toEqual()"
tags: [testing, pest, style]
kind: convention
---
# Pest Style

Write tests with `it()` and assert with strict `->toBe()`. This is the suite's usual style, though nothing enforces it.

## Rules

- Use `it('does x', fn () => ...)`. `test()` is rare and `describe()` is not used.
- `->toBe()` checks identity and exact type. Use `->toEqual()` only when loose equality is really what you mean, which is rare.

```php
it('roundtrips a tabular array', function () {
    expect(roundtrip($data))->toBe($data);
});
```
