<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use MarianDumitru\Netopay\Enums\PaymentStatus;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;
use MarianDumitru\Netopay\Events\NetopiaPaymentFailed;
use MarianDumitru\Netopay\Events\NetopiaPaymentPending;
use MarianDumitru\Netopay\Events\NetopiaReturnReceived;

// ── IPN ──────────────────────────────────────────────────────────────────────

it('fires NetopiaPaymentApproved when IPN status is paid', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error'   => ['code' => '00', 'message' => 'Approved'],
            'order'   => ['orderID' => 'order-paid'],
            'payment' => ['ntpID' => '123', 'status' => 3, 'amount' => 50.0, 'currency' => 'RON'],
            'status'  => PaymentStatus::Paid,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order'   => ['orderID' => 'order-paid'],
        'payment' => ['ntpID' => '123', 'status' => 3],
        'error'   => ['code' => '00', 'message' => 'Approved'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentApproved::class, fn ($e) =>
        $e->status->orderId === 'order-paid'
    );
});

it('fires NetopiaPaymentApproved when IPN status is confirmed', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error'   => ['code' => '00', 'message' => 'Approved'],
            'order'   => ['orderID' => 'order-confirmed'],
            'payment' => ['ntpID' => '456', 'status' => 5, 'amount' => 25.0, 'currency' => 'RON'],
            'status'  => PaymentStatus::Confirmed,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order'   => ['orderID' => 'order-confirmed'],
        'payment' => ['ntpID' => '456', 'status' => 5],
        'error'   => ['code' => '00', 'message' => 'Approved'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentApproved::class);
});

it('fires NetopiaPaymentPending when IPN status is awaiting 3DS', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error'   => ['code' => '102', 'message' => '3DS required'],
            'order'   => ['orderID' => 'order-3ds'],
            'payment' => ['ntpID' => '789', 'status' => 15, 'amount' => 10.0, 'currency' => 'RON'],
            'status'  => PaymentStatus::Awaiting3DS,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order'   => ['orderID' => 'order-3ds'],
        'payment' => ['ntpID' => '789', 'status' => 15],
        'error'   => ['code' => '102', 'message' => '3DS required'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentPending::class);
});

it('fires NetopiaPaymentFailed when IPN status is failed', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([
            'error'   => ['code' => '20', 'message' => 'Insufficient funds'],
            'order'   => ['orderID' => 'order-fail'],
            'payment' => ['ntpID' => '000', 'status' => 0, 'amount' => 10.0, 'currency' => 'RON'],
            'status'  => PaymentStatus::Failed,
        ], 200),
    ]);

    $this->postJson('/netopia/ipn', [
        'order'   => ['orderID' => 'order-fail'],
        'payment' => ['ntpID' => '000', 'status' => 0],
        'error'   => ['code' => '20', 'message' => 'Insufficient funds'],
    ])->assertNoContent();

    Event::assertDispatched(NetopiaPaymentFailed::class);
});

it('returns 204 even when retrieveStatus fails', function () {
    Event::fake();

    Http::fake([
        '*/operation/status' => Http::response([], 500),
    ]);

    $this->postJson('/netopia/ipn', [
        'order'   => ['orderID' => 'order-err'],
        'payment' => ['ntpID' => '111', 'status' => 3],
        'error'   => ['code' => '00', 'message' => 'Approved'],
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

    Event::assertDispatched(NetopiaReturnReceived::class, fn ($e) =>
        $e->orderId === 'order-return-1'
    );
});

it('fires NetopiaReturnReceived via POST return', function () {
    Event::fake();

    $this->post('/netopia/return', ['orderId' => 'order-return-2', 'someField' => 'value'])
        ->assertRedirect('/dashboard');

    Event::assertDispatched(NetopiaReturnReceived::class, fn ($e) =>
        $e->orderId === 'order-return-2' &&
        $e->formData === ['someField' => 'value']
    );
});