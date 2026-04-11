<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use MarianDumitru\Netopay\Dto\BillingData;
use MarianDumitru\Netopay\Dto\IpnPayloadDto;
use MarianDumitru\Netopay\Dto\OrderData;
use MarianDumitru\Netopay\Dto\StartConfigDto;
use MarianDumitru\Netopay\Dto\StartOrderDto;
use MarianDumitru\Netopay\Dto\StartPaymentDto;
use MarianDumitru\Netopay\Dto\StartPaymentRequestDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;
use MarianDumitru\Netopay\NetopiaClient;

function makeClient(): NetopiaClient
{
    return new NetopiaClient(
        apiKey:             'test-api-key',
        startEndpoint:      'https://secure.sandbox.netopia-payments.com/payment/card/start',
        statusEndpoint:     'https://secure.sandbox.netopia-payments.com/operation/status',
        verifyAuthEndpoint: 'https://secure.sandbox.netopia-payments.com/payment/card/verify-auth',
    );
}

function makeBilling(): BillingData
{
    return new BillingData(
        email:      'test@example.com',
        phone:      '0700000000',
        firstName:  'John',
        lastName:   'Doe',
        city:       'Bucharest',
        country:    642,
        state:      'Bucharest',
        postalCode: '010101',
        details:    '123 Main St',
    );
}

// ── start() ──────────────────────────────────────────────────────────────────

it('calls the start endpoint and returns a StartPaymentResponseDto', function () {
    Http::fake([
        '*/payment/card/start' => Http::response([
            'customerAction' => [],
            'error'          => ['code' => '101', 'message' => 'Redirect user to payment page'],
            'payment'        => [
                'ntpID'      => '2747182',
                'status'     => 1,
                'paymentURL' => 'https://secure-sandbox.netopia-payments.com/ui/card?p=XYZ',
            ],
        ], 200),
    ]);

    $orderDto   = new StartOrderDto('TEST-SIG', 'order-1', 'Test payment', 100.0, 'RON', makeBilling());
    $configDto  = new StartConfigDto();
    $paymentDto = StartPaymentDto::forHostedPage();
    $request    = StartPaymentRequestDto::build($orderDto, $configDto, $paymentDto);

    $response = makeClient()->start($request);

    expect($response->providerPaymentId)->toBe('2747182')
        ->and($response->providerStatusCode)->toBe(1)
        ->and($response->paymentUrl)->toBe('https://secure-sandbox.netopia-payments.com/ui/card?p=XYZ');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/payment/card/start') &&
        $req->hasHeader('Authorization', 'test-api-key')
    );
});

// ── retrieveStatus() ─────────────────────────────────────────────────────────

it('calls the status endpoint with ntpID and orderID', function () {
    Http::fake([
        '*/operation/status' => Http::response([
            'error'   => ['code' => '00', 'message' => 'Approved'],
            'order'   => ['orderID' => 'order-1', 'currency' => 'RON'],
            'payment' => ['ntpID' => '2747182', 'status' => 3, 'amount' => 100.0, 'currency' => 'RON'],
            'status'  => PaymentStatus::Paid,
        ], 200),
    ]);

    $dto = makeClient()->retrieveStatus('2747182', 'order-1');

    expect($dto->state)->toBe(PaymentStatus::Paid)
        ->and($dto->orderId)->toBe('order-1')
        ->and($dto->providerPaymentId)->toBe('2747182');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/operation/status') &&
        $req->data()['ntpID'] === '2747182' &&
        $req->data()['orderID'] === 'order-1'
    );
});

// ── handleIpn() ──────────────────────────────────────────────────────────────

it('parses an IPN payload without making an HTTP call', function () {
    Http::fake(); // nothing should be called

    $body = [
        'order'   => ['orderID' => 'ipn-order'],
        'payment' => ['ntpID' => '999', 'status' => 3, 'amount' => 50.0],
        'error'   => ['code' => '00', 'message' => 'Approved'],
    ];

    $dto = makeClient()->handleIpn(new IpnPayloadDto($body, []));

    expect($dto->orderId)->toBe('ipn-order')
        ->and($dto->state)->toBe(PaymentStatus::Paid);

    Http::assertNothingSent();
});

// ── verifyAuth() ──────────────────────────────────────────────────────────────

it('calls the verify-auth endpoint with the correct payload', function () {
    Http::fake([
        '*/payment/card/verify-auth' => Http::response([
            'error'   => ['code' => '00', 'message' => 'Approved'],
            'order'   => ['orderID' => 'order-3ds'],
            'payment' => [
                'ntpID'    => '555',
                'status'   => 3,
                'amount'   => 22.94,
                'currency' => 'RON',
                'token'    => 'card-token==',
                'data'     => ['AuthCode' => 'ABC1', 'RRN' => 'RRN123'],
            ],
            'status'  => PaymentStatus::Paid,
        ], 200),
    ]);

    $dto = makeClient()->verifyAuth('order-3ds', 'auth-token-xyz', '555', ['someFormField' => 'value']);

    expect($dto->authCode)->toBe('ABC1')
        ->and($dto->rrn)->toBe('RRN123')
        ->and($dto->paymentToken)->toBe('card-token==');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/payment/card/verify-auth') &&
        $req->data()['authenticationToken'] === 'auth-token-xyz' &&
        $req->data()['ntpID'] === '555'
    );
});