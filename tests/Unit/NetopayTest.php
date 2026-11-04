<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use MarianDumitru\Netopay\Dto\BillingDto;
use MarianDumitru\Netopay\Dto\OrderDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;
use MarianDumitru\Netopay\Facades\Netopay;

function netopayBilling(): BillingDto
{
    return new BillingDto(
        email: 'test@example.com',
        phone: '0700000000',
        firstName: 'John',
        lastName: 'Doe',
        city: 'Bucharest',
        country: 642,
        state: 'Bucharest',
        postalCode: '010101',
        details: '123 Main St',
    );
}

function fakeStartResponse(int $status = 1): void
{
    Http::fake([
        '*/payment/card/start' => Http::response([
            'customerAction' => [],
            'error' => ['code' => '101', 'message' => 'Redirect user to payment page'],
            'payment' => [
                'ntpID' => '2747182',
                'status' => $status,
                'paymentURL' => 'https://secure-sandbox.netopia-payments.com/ui/card?p=XYZ',
            ],
        ], 200),
    ]);
}

// ── start() ──────────────────────────────────────────────────────────────────

it('sends the configured redirect url when the order has none', function () {
    fakeStartResponse();

    Netopay::start(new OrderDto('order-1', 100.0, 'RON', 'Order #1', netopayBilling()));

    Http::assertSent(fn ($req) => $req->data()['config']['redirectUrl'] === 'https://example.com/netopia/return');
});

it('sends the order redirect url on a hosted page payment', function () {
    fakeStartResponse();

    Netopay::start(new OrderDto(
        orderId: 'order-2',
        amount: 100.0,
        currency: 'RON',
        description: 'Order #2',
        billing: netopayBilling(),
        redirectUrl: 'https://example.com/payments/mobile-return',
    ));

    Http::assertSent(fn ($req) => $req->data()['config']['redirectUrl'] === 'https://example.com/payments/mobile-return' &&
        $req->data()['config']['notifyUrl'] === 'https://example.com/netopia/ipn'
    );
});

it('sends a hosted page instrument without a token or MIT flag for a one-off payment', function () {
    fakeStartResponse();

    Netopay::start(new OrderDto('order-3', 45.5, 'RON', 'Table 4', netopayBilling()));

    Http::assertSent(fn ($req) => $req->data()['payment']['instrument'] === ['type' => 'card'] &&
        ! array_key_exists('scaExemptionInd', $req->data()['order'])
    );
});

// ── startWithToken() ─────────────────────────────────────────────────────────

it('sends the order redirect url on a token payment', function () {
    fakeStartResponse(3);

    Netopay::startWithToken(new OrderDto(
        orderId: 'order-4',
        amount: 100.0,
        currency: 'RON',
        description: 'Renewal',
        billing: netopayBilling(),
        merchantInitiated: true,
        redirectUrl: 'https://example.com/payments/mobile-return',
    ), 'saved-token==');

    Http::assertSent(fn ($req) => $req->data()['config']['redirectUrl'] === 'https://example.com/payments/mobile-return' &&
        $req->data()['payment']['instrument'] === ['type' => 'card', 'token' => 'saved-token==']
    );
});

it('marks a merchant-initiated token payment as MIT', function () {
    fakeStartResponse(3);

    Netopay::startWithToken(new OrderDto(
        orderId: 'order-5',
        amount: 100.0,
        currency: 'RON',
        description: 'Renewal',
        billing: netopayBilling(),
        merchantInitiated: true,
    ), 'saved-token==');

    Http::assertSent(fn ($req) => ($req->data()['order']['scaExemptionInd'] ?? null) === 'MIT');
});

it('does not mark a customer-present token payment as MIT', function () {
    fakeStartResponse(3);

    Netopay::startWithToken(
        new OrderDto('order-6', 45.5, 'RON', 'Table 4', netopayBilling()),
        'saved-token==',
    );

    Http::assertSent(fn ($req) => ! array_key_exists('scaExemptionInd', $req->data()['order']) &&
        $req->data()['config']['redirectUrl'] === 'https://example.com/netopia/return'
    );
});

// ── retrieveStatusById() ─────────────────────────────────────────────────────

it('retrieves the status by ntpID and orderID', function () {
    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '00', 'message' => 'Approved'],
            'order' => ['orderID' => 'order-7', 'currency' => 'EUR'],
            'payment' => ['ntpID' => '2739571', 'status' => 3, 'amount' => 22.94, 'currency' => 'RON', 'rrn' => 'RRN0292'],
        ], 200),
    ]);

    $status = Netopay::retrieveStatusById('2739571', 'order-7');

    expect($status->state)->toBe(PaymentStatus::Paid)
        ->and($status->orderId)->toBe('order-7')
        ->and($status->providerPaymentId)->toBe('2739571')
        ->and($status->rrn)->toBe('RRN0292');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/operation/status') &&
        $req->data() === ['ntpID' => '2739571', 'orderID' => 'order-7']
    );
});

it('sends the same status request as retrieveStatus', function () {
    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '00', 'message' => 'Approved'],
            'order' => ['orderID' => 'order-8'],
            'payment' => ['ntpID' => '555', 'status' => 5],
        ], 200),
    ]);

    $byId = Netopay::retrieveStatusById('555', 'order-8');
    $byDto = Netopay::retrieveStatus($byId);

    expect($byDto->state)->toBe($byId->state)
        ->and($byDto->state)->toBe(PaymentStatus::Confirmed);

    Http::assertSentCount(2);
});
