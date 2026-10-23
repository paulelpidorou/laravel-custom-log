<?php

namespace PaulElpidorou\CustomLog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use PaulElpidorou\CustomLog\Commands\CustomLogCommand;

class CustomLogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-custom-log')
            ->hasConfigFile();
    }
}
