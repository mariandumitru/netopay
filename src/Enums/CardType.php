<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

enum CardType: string
{
    case Visa             = 'visa';
    case MasterCard       = 'mastercard';
    case Amex             = 'amex';
    case NetopiaTestCard  = 'netopiatestcard';

    public static function getByFirstDigit(string $firstDigit): ?string
    {
        return match ($firstDigit) {
            '4' => self::Visa->value,
            '5' => self::MasterCard->value,
            '3' => self::Amex->value,
            '9' => self::NetopiaTestCard->value,
            default => null,
        };
    }
}
