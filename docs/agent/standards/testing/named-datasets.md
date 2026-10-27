---
description: "Dataset ->with() rows use descriptive string keys naming each edge case"
tags: [testing, pest, datasets]
kind: convention
---
# Named Datasets

Every `->with()` row has a descriptive string key that names the edge case, never a bare positional entry, so a failing case names itself in the test output.

## Rules

- The key names the boundary being tested: unicode, strings that look like TOON syntax, dotted keys, big integers.
- For a new behavior or edge case, add a named row rather than a new one-off test.

```php
})->with([
    'extreme floats' => [['a' => 1.7976931348623157e308, 'b' => 1e-7]],
    'strings resembling toon syntax' => [['a' => '[3]: x,y,z', 'b' => '- item']],
    'literal dotted key' => [['a.b' => 1]],
]);
```
