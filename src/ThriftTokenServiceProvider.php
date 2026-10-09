<?php

namespace RefactorCircus\ThriftToken;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\ThriftToken\Domains\DomainServiceProvider;

/**
 * Registers TOON configuration and the package's domains with Laravel.
 *
 * The Laravel macros (`Collection::toToon()` and friends) are registered by
 * the Integration domain's provider.
 */
class ThriftTokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/thrift-token.php', 'thrift-token');

        $this->app->register(DomainServiceProvider::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/thrift-token.php' => config_path('thrift-token.php'),
        ], 'thrift-token-config');
    }
}
