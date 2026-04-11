<?php

namespace MarianDumitru\Netopay;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MarianDumitru\Netopay\Commands\NetopayCommand;

class NetopayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('netopay')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_netopay_table')
            ->hasCommand(NetopayCommand::class);
    }
}
