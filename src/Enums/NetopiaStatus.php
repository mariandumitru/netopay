<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

enum NetopiaStatus: int
{
    case Paid           = 3;
    case Confirmed      = 5;
    case InvalidAccount = 12;
    case Awaiting3DS    = 15;

    public static function labels(): array
    {
        return [
            self::Paid->value           => 'is paid',
            self::Confirmed->value      => 'is confirmed',
            self::InvalidAccount->value => 'Invalid Account',
            self::Awaiting3DS->value    => 'need authorize',
        ];
    }

    public static function getLabelByStatus(int $status): string
    {
        return self::labels()[$status] ?? 'unknown';
    }
}
