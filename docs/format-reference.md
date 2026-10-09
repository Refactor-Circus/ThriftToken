# TOON Format Reference

TOON (Token-Oriented Object Notation) is a line-oriented, indentation-based text format that encodes the JSON data model with explicit structure and minimal quoting. ThriftToken targets `toon-spec: 4.4`.

For the full specification, see [toonformat.dev/reference/spec](https://toonformat.dev/reference/spec.html).

## Objects

Key-value pairs, one per line:

```
id: 123
name: Ada
active: true
```

Dotted keys such as `user.name` are ordinary keys; the dot has no structural meaning.

## Nested Objects

Indent child keys under the parent. A key with nothing after its colon and nothing indented below it is an empty object:

```
user:
  id: 123
  name: Ada
settings:
```

## Primitive Arrays (Inline)

Arrays of scalars are written inline with a count header:

```
tags[3]: admin,ops,dev
empty: []
```

The `[3]` declares the element count. Values are separated by the delimiter (comma by default).

## Tabular Arrays (Uniform Objects)

Arrays of objects sharing the same keys declare their fields once:

```
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
```

A column whose values are themselves uniform objects becomes a nested field group, expanded in place in each row:

```
orders[2]{id,customer{name,country}}:
  1,Ada,UK
  2,Bob,FR
```

## Keyed Tabular Objects

An object of two or more uniform objects puts each entry's key at the start of its row. `[N:]` declares the entry count:

```
users[2:]{age,city}:
  alice: 30,Paris
  bob: 25,Rome
```

This decodes to `{"users": {"alice": {"age": 30, "city": "Paris"}, "bob": {"age": 25, "city": "Rome"}}}`.

## List Items

Arrays that are neither all scalars nor uniform objects list one item per line. An object item carries its first field on the hyphen line and the rest below it; a bare `-` is an empty object:

```
items[4]:
  - 1
  - [2]: a,b
  - id: 7
    tags[2]: x,y
  -
```

## Comments

Decoders drop full-line comments: lines whose first non-space character is `#`. Encoders never write them, and strings starting with `#` are always quoted.

```
# Exported nightly
users[1]{id,name}:
  # a comment between rows does not end them
  1,Ada
```

## Alternate Delimiters

The default delimiter is comma. Tab and pipe are declared in every header, after the count:

```
items[2|]{id|name}:
  1|Alice
  2|Bob
```

```
items[2	]{id	name}:
  1	Alice
  2	Bob
```

## Special Values

| Value | TOON Representation |
|-------|-------------------|
| `null` | `null` |
| `true` | `true` |
| `false` | `false` |
| Empty string | `""` |
| Empty object | `key:` (bare colon, no value or children); an empty document at the root |
| Empty array | `key: []`, or `[]` at the root |

## Quoting and Escaping

A string is quoted when it is empty, has leading or trailing whitespace, equals `true`, `false` or `null`, looks like a number (`42`, `05`, `+1`, `1e-6`), contains a colon, quote, backslash, bracket, brace, control character or the delimiter, or starts with `-` or `#`. Inside quotes, only `\\`, `\"`, `\n`, `\r`, `\t` and `\u00XX` (for other control characters) are escapes; everything else is written as literal UTF-8.

Keys are written bare when they match `[A-Za-z_][A-Za-z0-9_.]*` and quoted otherwise.

## Numbers

Numbers are written in plain decimal with the fewest digits that read back exactly (`1.5`, not `1.50`; `1000000`, not `1e6`), except below `1e-6` or from `1e21` upwards, where exponent form such as `1e-7` is used. `NaN` and infinities are written as `null`.

Unquoted tokens are numbers only when they match the JSON number grammar, so `05`, `+1` and `NaN` decode as strings.
