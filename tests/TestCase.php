<?php

namespace Satheez\LaravelApiResponse\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Satheez\LaravelApiResponse\LaravelApiResponseServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelApiResponseServiceProvider::class,
        ];
    }
}
