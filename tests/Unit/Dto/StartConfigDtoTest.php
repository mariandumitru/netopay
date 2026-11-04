<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Dto\StartConfigDto;

it('uses the configured redirect url when none is given', function () {
    $dto = new StartConfigDto;

    expect($dto->redirectUrl)->toBe('https://example.com/netopia/return')
        ->and($dto->notifyUrl)->toBe('https://example.com/netopia/ipn');
});

it('uses the given redirect url over the configured one', function () {
    $dto = new StartConfigDto('https://example.com/payments/mobile-return');

    expect($dto->redirectUrl)->toBe('https://example.com/payments/mobile-return')
        ->and($dto->toArray()['redirectUrl'])->toBe('https://example.com/payments/mobile-return');
});

it('keeps the configured notify url when the redirect url is overridden', function () {
    $dto = new StartConfigDto('https://example.com/payments/mobile-return');

    expect($dto->notifyUrl)->toBe('https://example.com/netopia/ipn');
});

it('falls back to the configured redirect url when given an empty string', function () {
    $dto = new StartConfigDto('');

    expect($dto->redirectUrl)->toBe('https://example.com/netopia/return');
});

it('falls back to the package return route when no redirect url is configured or given', function () {
    config()->set('netopay.redirect_url', null);

    $dto = new StartConfigDto;

    expect($dto->redirectUrl)->toBe(route('netopia.return'));
});
