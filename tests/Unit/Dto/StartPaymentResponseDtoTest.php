<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Dto\StartPaymentResponseDto;

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
