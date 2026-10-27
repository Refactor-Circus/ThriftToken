---
description: "ThriftToken's exception tree: ToonEncodeException and ToonDecodeException (readonly ?int $toonLine, \"Line N: \" prefix) under ThriftTokenException, with ToonStrictModeException extending the decode exception for strict-only violations."
tags: [thrifttoken, errors, exceptions, decoding]
kind: convention
---
# ThriftToken Exceptions

Complements [[exceptions]].

Decode errors point to the line in the source TOON, and a strict-mode violation is a kind of decode error.

## Hierarchy

```
ThriftTokenException (extends RuntimeException)
├── ToonEncodeException
└── ToonDecodeException        ← readonly ?int $toonLine
    └── ToonStrictModeException
```

## Rules

- `ToonDecodeException` carries `?int $toonLine` and adds `"Line N: "` to the start of its message.
- When a throw on the decode path knows the offending line, it passes `$line`.
- A violation that only applies in strict mode throws `ToonStrictModeException`. Callers can catch it as a decode error or catch it on its own.
