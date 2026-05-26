<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Dto\PaymentStatusDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;

// ── fromNetopiaStatus ────────────────────────────────────────────────────────

it('parses a paid retrieve-status response', function () {
    $data = [
        'error' => ['code' => '00', 'message' => 'Approved'],
        'order' => ['orderID' => 'order-uuid-123', 'currency' => 'EUR'],
        'payment' => [
            'ntpID' => '2739571',
            'status' => 3,
            'amount' => 22.94,
            'currency' => 'RON',
            'rrn' => 'RRN0292',
        ],
        'status' => PaymentStatus::Paid,
    ];

    $dto = PaymentStatusDto::fromNetopiaStatus($data);

    expect($dto->orderId)->toBe('order-uuid-123')
        ->and($dto->providerPaymentId)->toBe('2739571')
        ->and($dto->state)->toBe(PaymentStatus::Paid)
        ->and($dto->rrn)->toBe('RRN0292')
        ->and($dto->amount)->toBe(22.94)
        ->and($dto->currency)->toBe('RON')
        ->and($dto->errorCode)->toBe('00')
        ->and($dto->paymentToken)->toBeNull();
});

it('reads payment token from retrieve-status response', function () {
    $data = [
        'error' => ['code' => '00', 'message' => 'Approved'],
        'order' => ['orderID' => 'order-abc'],
        'payment' => [
            'ntpID' => '111',
            'status' => 3,
            'amount' => 10.0,
            'currency' => 'RON',
            'token' => 'saved-card-token==',
        ],
        'status' => PaymentStatus::Paid,
    ];

    $dto = PaymentStatusDto::fromNetopiaStatus($data);

    expect($dto->paymentToken)->toBe('saved-card-token==');
});

// ── fromNetopiaVerifyAuth ────────────────────────────────────────────────────

it('parses a verify-auth response', function () {
    $data = [
        'error' => ['code' => '00', 'message' => 'Approved'],
        'order' => ['orderID' => 'order-3ds'],
        'payment' => [
            'ntpID' => '2739571',
            'status' => 3,
            'amount' => 22.94,
            'currency' => 'RON',
            'token' => 'MTEw----=',
            'data' => [
                'AuthCode' => 'QKlB',
                'RRN' => '8ZaCzKSE5Qpc',
                'BIN' => '990000',
            ],
        ],
        'status' => PaymentStatus::Paid,
    ];

    $dto = PaymentStatusDto::fromNetopiaVerifyAuth($data);

    expect($dto->authCode)->toBe('QKlB')
        ->and($dto->rrn)->toBe('8ZaCzKSE5Qpc')
        ->and($dto->paymentToken)->toBe('MTEw----=')
        ->and($dto->orderId)->toBe('order-3ds');
});

// ── fromIpnPayload ───────────────────────────────────────────────────────────

it('parses an IPN payload', function () {
    $payload = [
        'error' => ['code' => '00', 'message' => 'Approved'],
        'order' => ['orderID' => 'order-ipn'],
        'payment' => [
            'ntpID' => '777',
            'status' => 3,
            'amount' => 50.0,
            'currency' => 'RON',
        ],
        'status' => PaymentStatus::Paid,
    ];

    $dto = PaymentStatusDto::fromIpnPayload($payload);

    expect($dto->orderId)->toBe('order-ipn')
        ->and($dto->providerPaymentId)->toBe('777')
        ->and($dto->state)->toBe(PaymentStatus::Paid)
        ->and($dto->amount)->toBe(50.0);
});

// ── currency fallback ────────────────────────────────────────────────────────

it('falls back to config currency when not present in response', function () {
    $data = [
        'error' => ['code' => '00', 'message' => 'Approved'],
        'order' => ['orderID' => 'order-x'],
        'payment' => ['ntpID' => '1', 'status' => 3, 'amount' => 5.0],
        'status' => PaymentStatus::Paid,
    ];

    $dto = PaymentStatusDto::fromNetopiaStatus($data);

    expect($dto->currency)->toBe('RON'); // from config in TestCase
});
