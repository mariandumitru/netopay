<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Dto\StartPaymentResponseDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;

it('parses a hosted page start response', function () {
    $data = [
        'customerAction' => [],
        'error' => ['code' => '101', 'message' => 'Redirect user to payment page'],
        'payment' => [
            'amount' => 323.76,
            'binding' => ['expireMonth' => 0, 'expireYear' => 0],
            'currency' => 'RON',
            'ntpID' => '2747182',
            'paymentURL' => 'https://secure-sandbox.netopia-payments.com/ui/card?p=BwKP',
            'status' => 1,
        ],
    ];

    $dto = StartPaymentResponseDto::fromNetopiaStart($data);

    expect($dto->providerPaymentId)->toBe('2747182')
        ->and($dto->providerStatusCode)->toBe(1)
        ->and($dto->paymentUrl)->toBe('https://secure-sandbox.netopia-payments.com/ui/card?p=BwKP')
        ->and($dto->customerAction)->toBeNull()
        ->and($dto->token)->toBeNull()
        ->and($dto->rawResponse)->toBe(json_encode($data));
});

it('parses a response with a payment token', function () {
    $data = [
        'customerAction' => [],
        'error' => ['code' => '00', 'message' => 'Approved'],
        'payment' => [
            'ntpID' => '9999',
            'status' => 3,
            'token' => 'abc123token==',
        ],
    ];

    $dto = StartPaymentResponseDto::fromNetopiaStart($data);

    expect($dto->token)->toBe('abc123token==')
        ->and($dto->providerStatusCode)->toBe(3);
});

it('parses a 3DS response with customerAction', function () {
    $data = [
        'customerAction' => ['url' => 'https://bank.example.com/3ds', 'authenticationToken' => 'tok123'],
        'error' => ['code' => '102', 'message' => '3DS required'],
        'payment' => [
            'ntpID' => '1234',
            'status' => 15,
        ],
    ];

    $dto = StartPaymentResponseDto::fromNetopiaStart($data);

    expect($dto->customerAction)->toBe(['url' => 'https://bank.example.com/3ds', 'authenticationToken' => 'tok123'])
        ->and($dto->providerStatusCode)->toBe(15);
});

it('parses the auth code and RRN from a token payment paid on start', function () {
    $data = [
        'customerAction' => [],
        'error' => ['code' => '00', 'message' => 'Approved'],
        'payment' => [
            'ntpID' => '2739571',
            'status' => 3,
            'token' => 'abc123token==',
            'data' => [
                'AuthCode' => 'QKlB',
                'BIN' => '990000',
                'RRN' => '8ZaCzKSE5Qpc',
            ],
        ],
    ];

    $dto = StartPaymentResponseDto::fromNetopiaStart($data);

    expect($dto->authCode)->toBe('QKlB')
        ->and($dto->rrn)->toBe('8ZaCzKSE5Qpc')
        ->and($dto->state())->toBe(PaymentStatus::Paid);
});

it('leaves the auth code and RRN null on a hosted page start', function () {
    $dto = StartPaymentResponseDto::fromNetopiaStart([
        'customerAction' => [],
        'payment' => [
            'ntpID' => '2747182',
            'status' => 1,
            'paymentURL' => 'https://secure-sandbox.netopia-payments.com/ui/card?p=BwKP',
        ],
    ]);

    expect($dto->authCode)->toBeNull()
        ->and($dto->rrn)->toBeNull();
});

it('maps the start status code to a payment status', function (int $code, PaymentStatus $expected) {
    $dto = StartPaymentResponseDto::fromNetopiaStart([
        'payment' => ['ntpID' => '1', 'status' => $code],
    ]);

    expect($dto->state())->toBe($expected);
})->with([
    'paid' => [3, PaymentStatus::Paid],
    'confirmed' => [5, PaymentStatus::Confirmed],
    'awaiting 3DS' => [15, PaymentStatus::Awaiting3DS],
    'declined' => [12, PaymentStatus::Failed],
    'hosted page new' => [1, PaymentStatus::Pending],
]);

it('maps a missing status to unknown', function () {
    $dto = StartPaymentResponseDto::fromNetopiaStart(['payment' => ['ntpID' => '1']]);

    expect($dto->providerStatusCode)->toBe(0)
        ->and($dto->state())->toBe(PaymentStatus::Unknown);
});
