<?php

namespace MarianDumitru\Netopay\Tests;

use MarianDumitru\Netopay\NetopayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NetopayServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('netopay.sandbox', true);
        config()->set('netopay.sandbox_credentials.api_key', 'test-api-key');
        config()->set('netopay.sandbox_credentials.pos_signature', 'TEST-POS-SIG');
        config()->set('netopay.live.api_key', 'live-api-key');
        config()->set('netopay.live.pos_signature', 'LIVE-POS-SIG');
        config()->set('netopay.endpoints.sandbox.start', 'https://secure.sandbox.netopia-payments.com/payment/card/start');
        config()->set('netopay.endpoints.sandbox.status', 'https://secure.sandbox.netopia-payments.com/operation/status');
        config()->set('netopay.endpoints.sandbox.verify_auth', 'https://secure.sandbox.netopia-payments.com/payment/card/verify-auth');
        config()->set('netopay.endpoints.live.start', 'https://secure.mobilpay.ro/pay/payment/card/start');
        config()->set('netopay.endpoints.live.status', 'https://secure.mobilpay.ro/pay/operation/status');
        config()->set('netopay.endpoints.live.verify_auth', 'https://secure.mobilpay.ro/pay/payment/card/verify-auth');
        config()->set('netopay.notify_url', 'https://example.com/netopia/ipn');
        config()->set('netopay.redirect_url', 'https://example.com/netopia/return');
        config()->set('netopay.after_payment_redirect', '/dashboard');
        config()->set('netopay.currency', 'RON');
        config()->set('netopay.routes.enabled', true);
        config()->set('netopay.routes.prefix', 'netopia');
        config()->set('netopay.routes.middleware', []);
    }
}
