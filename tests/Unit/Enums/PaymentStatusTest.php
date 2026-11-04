<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Enums\NetopiaStatus;
use MarianDumitru\Netopay\Enums\PaymentStatus;

it('maps each netopia status code to a payment status', function (int $code, PaymentStatus $expected) {
    expect(PaymentStatus::getStatus($code))->toBe($expected);
})->with([
    'new' => [1, PaymentStatus::Pending],
    'opened' => [2, PaymentStatus::Pending],
    'paid' => [3, PaymentStatus::Paid],
    'confirmed' => [5, PaymentStatus::Confirmed],
    'pending' => [6, PaymentStatus::Pending],
    'error' => [11, PaymentStatus::Failed],
    'invalid account' => [12, PaymentStatus::Failed],
    'fraud' => [13, PaymentStatus::Failed],
    'pending auth' => [14, PaymentStatus::Pending],
    'awaiting 3DS' => [15, PaymentStatus::Awaiting3DS],
    'pending any' => [18, PaymentStatus::Pending],
]);

it('maps a missing or unrecognised status code to Unknown, never Failed', function (int $code) {
    expect(PaymentStatus::getStatus($code))->toBe(PaymentStatus::Unknown);
})->with([
    'missing (0)' => [0],
    'canceled (4)' => [4],
    'credit (8)' => [8],
    'chargeback (9)' => [9],
    'reversed (17)' => [17],
    'expired (23)' => [23],
    'out of range' => [99],
    'negative' => [-1],
]);

it('has a label for every netopia status', function (NetopiaStatus $status) {
    expect(NetopiaStatus::getLabelByStatus($status->value))->not->toBe('unknown');
})->with(NetopiaStatus::cases());

it('labels an unrecognised netopia status as unknown', function () {
    expect(NetopiaStatus::getLabelByStatus(99))->toBe('unknown');
});
