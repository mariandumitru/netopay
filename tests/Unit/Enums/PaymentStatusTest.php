<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Enums\PaymentStatus;

it('maps netopia status 3 to Paid', function () {
    expect(PaymentStatus::getStatus(3))->toBe(PaymentStatus::Paid);
});

it('maps netopia status 5 to Confirmed', function () {
    expect(PaymentStatus::getStatus(5))->toBe(PaymentStatus::Confirmed);
});

it('maps netopia status 15 to Awaiting3DS', function () {
    expect(PaymentStatus::getStatus(15))->toBe(PaymentStatus::Awaiting3DS);
});

it('maps unknown netopia status to Failed', function () {
    expect(PaymentStatus::getStatus(0))->toBe(PaymentStatus::Failed);
    expect(PaymentStatus::getStatus(99))->toBe(PaymentStatus::Failed);
    expect(PaymentStatus::getStatus(12))->toBe(PaymentStatus::Failed);
});