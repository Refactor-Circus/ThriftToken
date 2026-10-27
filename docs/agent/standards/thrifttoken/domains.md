---
description: "ThriftToken's domains: framework-free Encoding and Decoding with no providers, and Integration holding Laravel/third-party code in one subfolder per vendor (Ai, Pao); the core never imports Integration."
tags: [thrifttoken, domains, layout, integrations]
kind: architectural
---
# ThriftToken Domains

Complements [[domain-modules]].

ThriftToken has three domains: framework-free **Encoding** and **Decoding**, and **Integration** for everything coupled to Laravel or a third-party package.

## Layout

```
src/
├── ThriftToken.php                    # static API / facade target
├── ThriftTokenServiceProvider.php     # config merge + publish tag; registers DomainServiceProvider
├── Facades/ThriftToken.php
├── Exceptions/ThriftTokenException.php
└── Domains/
    ├── DomainServiceProvider.php
    ├── Encoding/   Data/EncoderOptions  Enums/Delimiter  Exceptions/  Support/{ToonEncoder,ValueEncoder,NumberEncoder,ObjectValue}
    ├── Decoding/   Data/{DecoderOptions,Header,HeaderField}  Exceptions/  Support/{ToonDecoder,ValueDecoder,HeaderParser,Scanner,DecodedObject}
    └── Integration/
        ├── IntegrationServiceProvider.php   # toToon()/toon() macros
        ├── Concerns/HasToon.php
        ├── Ai/    # laravel/ai (tool results, middleware)
        └── Pao/   # laravel/pao (Pest output filter)
```

## Rules

- Encoding and Decoding have no service provider because they have nothing to wire.
- A new third-party integration gets its own vendor subfolder under `Domains/Integration/`.
- Encoding and Decoding may import each other. `Delimiter` lives in Encoding because it is an encoder option; Decoding reads it from the array header.
- Neither Encoding nor Decoding may import from Integration.
