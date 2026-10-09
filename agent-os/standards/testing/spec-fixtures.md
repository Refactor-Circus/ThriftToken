# Spec Fixtures

The TOON specification's language-agnostic fixtures, vendored in `tests/Fixtures/spec/`, are the conformance contract. `tests/Unit/SpecConformanceTest.php` runs every one of them.

- Refresh with `bin/sync-spec-fixtures.sh [ref]`; it records the spec version and commit in `spec.json`. Never hand-edit a fixture.
- Moving to a new spec version: sync, run the suite, fix the implementation until every fixture passes, then update `toon-spec:` in the README and docs.
- Fixtures compare in the JSON model: objects decode with `associative: false`, numbers compare by value.
- Behavior the fixtures cannot express (PHP host types, `associative` mode, out-of-range numbers, ill-formed UTF-8) gets a named test in `tests/Unit/HostModelTest.php`.
