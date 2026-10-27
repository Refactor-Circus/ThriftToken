---
description: "The spec's fixture suite is the conformance contract; sync it, never edit it"
tags: [thrifttoken, testing, toon-spec, fixtures]
kind: convention
---
# Spec Fixtures

The TOON spec's language-agnostic fixtures in `tests/Fixtures/spec/` are the conformance contract, and `tests/Unit/SpecConformanceTest.php` runs every one of them.

## Rules

- Refresh the fixtures with `bin/sync-spec-fixtures.sh [ref]`, which records the spec version and commit in `spec.json`. Never edit a fixture by hand.
- To move to a new spec version: sync, run the suite, and fix the implementation until every fixture passes. Then update `toon-spec:` in the README and docs.
- Fixtures are compared in the JSON model: objects decode with `associative: false`, and numbers compare by value.
- Behavior the fixtures cannot express (PHP host types, `associative` mode, out-of-range numbers, ill-formed UTF-8) gets a named test in `tests/Unit/HostModelTest.php`.
