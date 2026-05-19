<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse;

use Satheez\LaravelApiResponse\Support\ResponseMacroRegistrar;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaravelApiResponseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('api-response')
            ->hasConfigFile('api-response')
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->alias(ResponseFactory::class, 'api-response');
    }

    public function packageBooted(): void
    {
        $this->app->make(ResponseMacroRegistrar::class)->register();
    }
}
