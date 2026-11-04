<?php

declare(strict_types=1);

function loadNetopayConfig(): array
{
    return require __DIR__.'/../../config/netopay.php';
}

afterEach(function () {
    putenv('NETOPIA_API_URL_LIVE');
});

it('reads the live start endpoint from NETOPIA_API_URL_LIVE', function () {
    putenv('NETOPIA_API_URL_LIVE=https://custom.example.com/pay/payment/card/start');

    expect(loadNetopayConfig()['endpoints']['live']['start'])
        ->toBe('https://custom.example.com/pay/payment/card/start');
});

it('falls back to the default live start endpoint', function () {
    putenv('NETOPIA_API_URL_LIVE');

    expect(loadNetopayConfig()['endpoints']['live']['start'])
        ->toBe('https://secure.mobilpay.ro/pay/payment/card/start');
});
