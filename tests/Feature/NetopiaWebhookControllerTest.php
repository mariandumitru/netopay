<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;
use MarianDumitru\Netopay\Events\NetopiaIpnProcessingFailed;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;
use MarianDumitru\Netopay\Events\NetopiaPaymentChargeback;
use MarianDumitru\Netopay\Events\NetopiaPaymentFailed;
use MarianDumitru\Netopay\Events\NetopiaPaymentPending;
use MarianDumitru\Netopay\Events\NetopiaPaymentRefunded;
use MarianDumitru\Netopay\Events\NetopiaReturnReceived;

// ── IPN ──────────────────────────────────────────────────────────────────────

it('fires NetopiaPaymentApproved when IPN status is paid', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '00', 'message' => 'Approved'],
            'order' => ['orderID' => 'order-paid'],
            'payment' => ['ntpID' => '123', 'status' => 3, 'amount' => 50.0, 'currency' => 'RON'],
            'status' => PaymentStatus::Paid,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-paid'],
        'payment' => ['ntpID' => '123', 'status' => 3],
        'error' => ['code' => '00', 'message' => 'Approved'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentApproved::class, fn ($e) => $e->status->orderId === 'order-paid'
    );
});

it('fires NetopiaPaymentApproved when IPN status is confirmed', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '00', 'message' => 'Approved'],
            'order' => ['orderID' => 'order-confirmed'],
            'payment' => ['ntpID' => '456', 'status' => 5, 'amount' => 25.0, 'currency' => 'RON'],
            'status' => PaymentStatus::Confirmed,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-confirmed'],
        'payment' => ['ntpID' => '456', 'status' => 5],
        'error' => ['code' => '00', 'message' => 'Approved'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentApproved::class);
});

it('fires NetopiaPaymentPending when IPN status is awaiting 3DS', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '102', 'message' => '3DS required'],
            'order' => ['orderID' => 'order-3ds'],
            'payment' => ['ntpID' => '789', 'status' => 15, 'amount' => 10.0, 'currency' => 'RON'],
            'status' => PaymentStatus::Awaiting3DS,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-3ds'],
        'payment' => ['ntpID' => '789', 'status' => 15],
        'error' => ['code' => '102', 'message' => '3DS required'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentPending::class);
});

it('fires NetopiaPaymentFailed when IPN status is failed', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '20', 'message' => 'Insufficient funds'],
            'order' => ['orderID' => 'order-fail'],
            'payment' => ['ntpID' => '000', 'status' => 12, 'amount' => 10.0, 'currency' => 'RON'],
            'status' => PaymentStatus::Failed,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-fail'],
        'payment' => ['ntpID' => '000', 'status' => 12],
        'error' => ['code' => '20', 'message' => 'Insufficient funds'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentFailed::class);
});

it('fires the event that matches each status category', function (int $code, string $expected) {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'order' => ['orderID' => 'order-category'],
            'payment' => ['ntpID' => '500', 'status' => $code],
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-category'],
        'payment' => ['ntpID' => '500', 'status' => $code],
    ])->assertNoContent();

    $events = [
        NetopiaPaymentApproved::class,
        NetopiaPaymentPending::class,
        NetopiaPaymentFailed::class,
        NetopiaPaymentRefunded::class,
        NetopiaPaymentChargeback::class,
    ];

    Event::assertDispatched($expected, fn ($e) => $e->status->providerStatusCode === $code &&
        $e->status->state === PaymentStatus::getStatus($code)
    );

    foreach (array_diff($events, [$expected]) as $other) {
        Event::assertNotDispatched($other);
    }
})->with([
    'new' => [1, NetopiaPaymentPending::class],
    'opened' => [2, NetopiaPaymentPending::class],
    'paid' => [3, NetopiaPaymentApproved::class],
    'canceled' => [4, NetopiaPaymentFailed::class],
    'confirmed' => [5, NetopiaPaymentApproved::class],
    'pending' => [6, NetopiaPaymentPending::class],
    'credit' => [8, NetopiaPaymentRefunded::class],
    'chargeback init' => [9, NetopiaPaymentChargeback::class],
    'chargeback accept' => [10, NetopiaPaymentChargeback::class],
    'error' => [11, NetopiaPaymentFailed::class],
    'invalid account' => [12, NetopiaPaymentFailed::class],
    'fraud review' => [13, NetopiaPaymentPending::class],
    'pending auth' => [14, NetopiaPaymentPending::class],
    'awaiting 3DS' => [15, NetopiaPaymentPending::class],
    'chargeback representment' => [16, NetopiaPaymentChargeback::class],
    'reversed' => [17, NetopiaPaymentRefunded::class],
    'pending any' => [18, NetopiaPaymentPending::class],
    'expired' => [23, NetopiaPaymentFailed::class],
]);

it('fires no payment event and logs a warning for an unknown status', function (array $payment) {
    Event::fake();
    Log::spy();

    Http::fake([
        '*/operation/status' => Http::response([
            'order' => ['orderID' => 'order-unknown'],
            'payment' => $payment,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-unknown'],
        'payment' => $payment,
    ])->assertNoContent();

    Event::assertNotDispatched(NetopiaPaymentApproved::class);
    Event::assertNotDispatched(NetopiaPaymentPending::class);
    Event::assertNotDispatched(NetopiaPaymentFailed::class);
    Event::assertNotDispatched(NetopiaPaymentRefunded::class);
    Event::assertNotDispatched(NetopiaPaymentChargeback::class);
    Event::assertNotDispatched(NetopiaIpnProcessingFailed::class);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message, $context) => str_contains((string) $message, 'unknown payment status') &&
            $context['order_id'] === 'order-unknown' &&
            $context['provider_status_code'] === ($payment['status'] ?? null)
        );
})->with([
    'unrecognised code' => [['ntpID' => '600', 'status' => 99]],
    'subscription code' => [['ntpID' => '603', 'status' => 19]],
    'status 0' => [['ntpID' => '601', 'status' => 0]],
    'missing payment.status' => [['ntpID' => '602']],
]);

it('returns 204 even when retrieveStatus fails', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([], 500),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-err'],
        'payment' => ['ntpID' => '111', 'status' => 3],
        'error' => ['code' => '00', 'message' => 'Approved'],
    ])->assertNoContent();

    Event::assertNotDispatched(NetopiaPaymentApproved::class);
    Event::assertNotDispatched(NetopiaPaymentFailed::class);
    Event::assertNotDispatched(NetopiaPaymentPending::class);
});

// ── Return ───────────────────────────────────────────────────────────────────

it('fires NetopiaReturnReceived and redirects on return', function () {
    Event::fake();

    $this->get('/netopia/return?orderId=order-return-1')
        ->assertRedirect('/dashboard');

    Event::assertDispatched(NetopiaReturnReceived::class, fn ($e) => $e->orderId === 'order-return-1'
    );
});

it('fires NetopiaReturnReceived via POST return', function () {
    Event::fake();

    $this->post('/netopia/return', ['orderId' => 'order-return-2', 'someField' => 'value'])
        ->assertRedirect('/dashboard');

    Event::assertDispatched(NetopiaReturnReceived::class, fn ($e) => $e->orderId === 'order-return-2' &&
        $e->formData === ['someField' => 'value']
    );
});

it('logs a warning and still dispatches when orderId is missing on return', function () {
    Event::fake();
    Log::spy();

    $this->post('/netopia/return', ['someField' => 'value'])
        ->assertRedirect('/dashboard');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn ($message) => str_contains((string) $message, 'no orderId'));

    Event::assertDispatched(NetopiaReturnReceived::class, fn ($e) => $e->orderId === '' &&
        $e->formData === ['someField' => 'value']
    );
});

it('fires NetopiaIpnProcessingFailed when retrieveStatus throws', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([], 500),
    ]);

    $this->postJson('/netopia/ipn', [
        'order' => ['orderID' => 'order-broken'],
        'payment' => ['ntpID' => '222', 'status' => 3],
        'error' => ['code' => '00', 'message' => 'Approved'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaIpnProcessingFailed::class, fn ($e) => $e->payload['order']['orderID'] === 'order-broken' &&
        $e->exception !== null
    );
});

// ── IPN payload on events ────────────────────────────────────────────────────

it('passes the raw IPN body with the card token to NetopiaPaymentApproved', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '00', 'message' => 'Approved'],
            'order' => ['orderID' => 'order-card'],
            'payment' => [
                'ntpID' => '2739571',
                'status' => 3,
                'amount' => 22.94,
                'currency' => 'RON',
                'binding' => ['expireMonth' => 0, 'expireYear' => 0],
                'rrn' => 'RRN0292',
            ],
        ], 200),
    ]);

    $ipnBody = [
        'order' => ['orderID' => 'order-card'],
        'payment' => [
            'ntpID' => '2739571',
            'status' => 3,
            'amount' => 22.94,
            'currency' => 'RON',
            'token' => 'MTEwcard==',
            'binding' => ['token' => 'MTEwcard==', 'expireMonth' => 5, 'expireYear' => 2035],
            'instrument' => ['panMasked' => '9900****0000'],
        ],
        'error' => ['code' => '00', 'message' => 'Approved'],
    ];

    $this->postJson('/netopia/ipn', $ipnBody)->assertNoContent();

    Event::assertDispatched(NetopiaPaymentApproved::class, fn ($e) => $e->ipnPayload === $ipnBody &&
        $e->ipnPayload['payment']['token'] === 'MTEwcard==' &&
        $e->ipnPayload['payment']['binding']['expireMonth'] === 5 &&
        $e->ipnPayload['payment']['instrument']['panMasked'] === '9900****0000' &&
        $e->status->paymentToken === null &&
        $e->status->rrn === 'RRN0292'
    );
});

it('passes the raw IPN body to NetopiaPaymentApproved for a one-off payment without a card token', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '00', 'message' => 'Approved'],
            'order' => ['orderID' => 'order-table-4'],
            'payment' => ['ntpID' => '321', 'status' => 3, 'amount' => 45.5, 'currency' => 'RON'],
        ], 200),
    ]);

    $ipnBody = [
        'order' => ['orderID' => 'order-table-4'],
        'payment' => ['ntpID' => '321', 'status' => 3, 'amount' => 45.5, 'currency' => 'RON'],
        'error' => ['code' => '00', 'message' => 'Approved'],
    ];

    $this->postJson('/netopia/ipn', $ipnBody)->assertNoContent();

    Event::assertDispatched(NetopiaPaymentApproved::class, fn ($e) => $e->ipnPayload === $ipnBody &&
        ! isset($e->ipnPayload['payment']['token']) &&
        $e->status->state === PaymentStatus::Paid
    );
});

it('passes the raw IPN body to NetopiaPaymentPending', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '102', 'message' => '3DS required'],
            'order' => ['orderID' => 'order-3ds-payload'],
            'payment' => ['ntpID' => '789', 'status' => 15],
        ], 200),
    ]);

    $ipnBody = [
        'order' => ['orderID' => 'order-3ds-payload'],
        'payment' => ['ntpID' => '789', 'status' => 15],
        'error' => ['code' => '102', 'message' => '3DS required'],
    ];

    $this->postJson('/netopia/ipn', $ipnBody)->assertNoContent();

    Event::assertDispatched(NetopiaPaymentPending::class, fn ($e) => $e->ipnPayload === $ipnBody);
});

it('passes the raw IPN body to NetopiaPaymentFailed', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error' => ['code' => '20', 'message' => 'Insufficient funds'],
            'order' => ['orderID' => 'order-fail-payload'],
            'payment' => ['ntpID' => '000', 'status' => 12],
        ], 200),
    ]);

    $ipnBody = [
        'order' => ['orderID' => 'order-fail-payload'],
        'payment' => ['ntpID' => '000', 'status' => 12],
        'error' => ['code' => '20', 'message' => 'Insufficient funds'],
    ];

    $this->postJson('/netopia/ipn', $ipnBody)->assertNoContent();

    Event::assertDispatched(NetopiaPaymentFailed::class, fn ($e) => $e->ipnPayload === $ipnBody);
});

it('defaults the IPN payload to an empty array when an event is built without one', function () {
    $status = new PaymentStatusDto('order-x', '1', PaymentStatus::Paid);

    expect((new NetopiaPaymentApproved($status))->ipnPayload)->toBe([])
        ->and((new NetopiaPaymentPending($status))->ipnPayload)->toBe([])
        ->and((new NetopiaPaymentFailed($status))->ipnPayload)->toBe([])
        ->and((new NetopiaPaymentRefunded($status))->ipnPayload)->toBe([])
        ->and((new NetopiaPaymentChargeback($status))->ipnPayload)->toBe([]);
});
