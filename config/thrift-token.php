<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Encoder and Decoder Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults used by ThriftToken::encode() and ThriftToken::decode() when
    | no options are passed. Explicit options given by the caller always take
    | precedence.
    |
    | indent_size: spaces per indentation level, for encoding and decoding.
    | delimiter: the document delimiter, "comma", "tab", or "pipe".
    | strict: whether decoding errors on every malformed input (spec §14).
    |
    */
    'indent_size' => 2,
    'delimiter' => 'comma',
    'strict' => true,

    /*
    |--------------------------------------------------------------------------
    | PAO Test Output Override
    |--------------------------------------------------------------------------
    |
    | When enabled, test output from PAO (laravel/pao) will be encoded as
    | TOON instead of JSON. Only takes effect when PAO is installed.
    |
    | Set the THRIFT_TOKEN_PAO_OUTPUT environment variable to "true" to enable.
    |
    */
    'pao_output' => env('THRIFT_TOKEN_PAO_OUTPUT', false),
];
