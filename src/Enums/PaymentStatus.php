<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

enum PaymentStatus: string
{
    case Pending    = 'pending';
    case Paid       = 'paid';
    case Confirmed  = 'confirmed';
    case Failed     = 'failed';
    case Awaiting3DS = 'awaiting_3ds';

    public static function getStatus(int $providerStatus): self
    {
        return match ($providerStatus) {
            NetopiaStatus::Awaiting3DS->value => self::Awaiting3DS,
            NetopiaStatus::Paid->value        => self::Paid,
            NetopiaStatus::Confirmed->value   => self::Confirmed,
            default                           => self::Failed,
        };
    }
}
