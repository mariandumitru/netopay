<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay;

use Illuminate\Support\Facades\Route;
use MarianDumitru\Netopay\Contracts\NetopiaClientInterface;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NetopayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('netopay')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(NetopiaClientInterface::class, function () {
            $sandbox = config('netopay.sandbox', true);

            $apiKey = $sandbox
                ? config('netopay.sandbox_credentials.api_key')
                : config('netopay.live.api_key');

            $startEndpoint = $sandbox
                ? config('netopay.endpoints.sandbox.start')
                : config('netopay.endpoints.live.start');

            $statusEndpoint = $sandbox
                ? config('netopay.endpoints.sandbox.status')
                : config('netopay.endpoints.live.status');

            $verifyAuthEndpoint = $sandbox
                ? config('netopay.endpoints.sandbox.verify_auth')
                : config('netopay.endpoints.live.verify_auth');

            return new NetopiaClient(
                apiKey: $apiKey,
                startEndpoint: $startEndpoint,
                statusEndpoint: $statusEndpoint,
                verifyAuthEndpoint: $verifyAuthEndpoint,
            );
        });

        $this->app->singleton(Netopay::class, function ($app) {
            return new Netopay($app->make(NetopiaClientInterface::class));
        });
    }

    public function packageBooted(): void
    {
        if (config('netopay.routes.enabled', true)) {
            $prefix = config('netopay.routes.prefix', 'netopia');
            $middleware = config('netopay.routes.middleware', []);

            Route::prefix($prefix)
                ->middleware($middleware)
                ->group(__DIR__.'/../routes/web.php');
        }
    }
}
