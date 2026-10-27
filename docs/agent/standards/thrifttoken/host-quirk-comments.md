---
description: "Document host-framework lifecycle quirks and failure mode inline so they aren't \"fixed\""
tags: [thrifttoken, integrations, comments, pao]
kind: convention
---
# Host-Quirk Comments

When an integration works around an ordering or lifecycle quirk in its host framework, a comment at the workaround explains why it is there and what breaks without it.

## Rules

- Any deliberate omission or unusual coupling to a host contract gets an inline comment with the reason and the failure mode.
- The comment is required. It stops the next reader from tidying away behavior that was chosen on purpose.

## Current workarounds

- `Pao\Plugin` does not implement `Terminable` on purpose. Pest runs `terminate()` in registration order, so ThriftToken's filter would detach before PAO writes its JSON. The filter stays attached and PHP removes it at shutdown.
- `ToonOutputFilter` targets the `STDOUT` constant because Symfony's ConsoleOutput writes to it directly on Unix.
- Do not add `Terminable`, detach the filter or tidy the STDOUT handling. Each change silently breaks interception.
