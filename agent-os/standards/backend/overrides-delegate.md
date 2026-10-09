# Overrides Adapt, Then Delegate

An override's only job: bridge the host framework's extension point to `ThriftToken`. Zero encoding/format logic in overrides.

Each override adapts one host hook → `ThriftToken::encode`/`ThriftToken::smart`:

- `EncodesToonToolResults` trait — `handle()` runs tool, wraps result in `ToonToolResponse`
- `ToonToolResponse` — `Stringable`, `__toString()` returns `ThriftToken::smart($data)`
- `ToonOutputFilter` — stream filter, re-encodes via `ThriftToken::encode($decoded)`
- `ToonMiddleware` — appends format instructions to the prompt (no encoding, but still just adapts the hook)

```php
public function __construct(mixed $data) { $this->encoded = ThriftToken::smart($data); }
```

Rule: new override → adapt the host contract, call `ThriftToken::*`. Don't reimplement encoding. Same principle as [[thin-facade]] and [[totoon-surface]].
