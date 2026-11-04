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
    'canceled' => [4, PaymentStatus::Cancelled],
    'confirmed' => [5, PaymentStatus::Confirmed],
    'pending' => [6, PaymentStatus::Pending],
    'credit' => [8, PaymentStatus::Refunded],
    'chargeback init' => [9, PaymentStatus::Chargeback],
    'chargeback accept' => [10, PaymentStatus::Chargeback],
    'error' => [11, PaymentStatus::Failed],
    'invalid account' => [12, PaymentStatus::Failed],
    'fraud review' => [13, PaymentStatus::Pending],
    'pending auth' => [14, PaymentStatus::Pending],
    'awaiting 3DS' => [15, PaymentStatus::Awaiting3DS],
    'chargeback representment' => [16, PaymentStatus::Chargeback],
    'reversed' => [17, PaymentStatus::Refunded],
    'pending any' => [18, PaymentStatus::Pending],
    'expired' => [23, PaymentStatus::Expired],
]);

it('maps a missing or unrecognised status code to Unknown, never Failed', function (int $code) {
    expect(PaymentStatus::getStatus($code))->toBe(PaymentStatus::Unknown);
})->with([
    'missing (0)' => [0],
    'scheduled (7)' => [7],
    'programmed recurrent (19)' => [19],
    'canceled programmed recurrent (20)' => [20],
    'trial pending (21)' => [21],
    'trial (22)' => [22],
    'out of range (24)' => [24],
    'out of range (99)' => [99],
    'negative' => [-1],
]);

it('has a label for every netopia status', function (NetopiaStatus $status) {
    expect(NetopiaStatus::getLabelByStatus($status->value))->not->toBe('unknown');
})->with(NetopiaStatus::cases());

it('aliases Declined to status 12', function () {
    expect(NetopiaStatus::Declined)->toBe(NetopiaStatus::InvalidAccount)
        ->and(NetopiaStatus::Declined->value)->toBe(12);
});

it('labels an unrecognised netopia status as unknown', function () {
    expect(NetopiaStatus::getLabelByStatus(99))->toBe('unknown');
});

it('labels fraud (13) as a review hold', function () {
    expect(NetopiaStatus::getLabelByStatus(13))->toBe('payment in review');
});
