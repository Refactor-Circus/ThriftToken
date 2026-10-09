#!/usr/bin/env bash
# Refresh tests/Fixtures/spec from the TOON specification repository.
#
#   bin/sync-spec-fixtures.sh [ref]    # default: main
set -euo pipefail

ref="${1:-main}"
root="$(cd "$(dirname "$0")/.." && pwd)"
target="$root/tests/Fixtures/spec"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

git clone --quiet https://github.com/toon-format/spec.git "$tmp/spec"
git -C "$tmp/spec" checkout --quiet "$ref"

rm -rf "$target/encode" "$target/decode"
cp -R "$tmp/spec/tests/fixtures/encode" "$tmp/spec/tests/fixtures/decode" "$target/"
cp "$tmp/spec/LICENSE" "$target/LICENSE"

version="$(sed -n 's/^\*\*Version:\*\* //p' "$tmp/spec/SPEC.md")"
commit="$(git -C "$tmp/spec" rev-parse HEAD)"
printf '{\n    "version": "%s",\n    "commit": "%s"\n}\n' "$version" "$commit" > "$target/spec.json"

echo "Fixtures synced: TOON $version ($commit)"
