<?php

namespace RefactorCircus\ThriftToken\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use RefactorCircus\ThriftToken\ThriftTokenServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ThriftTokenServiceProvider::class,
        ];
    }
}
