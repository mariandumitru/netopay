<?php

declare(strict_types=1);

use MarianDumitru\Netopay\Enums\CardType;

it('detects Visa by first digit 4', function () {
    expect(CardType::getByFirstDigit('4'))->toBe('visa');
});

it('detects MasterCard by first digit 5', function () {
    expect(CardType::getByFirstDigit('5'))->toBe('mastercard');
});

it('detects Amex by first digit 3', function () {
    expect(CardType::getByFirstDigit('3'))->toBe('amex');
});

it('detects Netopia test card by first digit 9', function () {
    expect(CardType::getByFirstDigit('9'))->toBe('netopiatestcard');
});

it('returns null for unknown first digit', function () {
    expect(CardType::getByFirstDigit('1'))->toBeNull();
    expect(CardType::getByFirstDigit('7'))->toBeNull();
});
