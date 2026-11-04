<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

/**
 * Netopia payment status codes.
 *
 * NETOPIA's v2 docs list only 3, 5, 12 and 15. The other codes are not
 * officially documented: 1 comes back from a hosted-page start, and the rest
 * match what other NETOPIA integrations use. Codes not listed here map to
 * PaymentStatus::Unknown.
 */
enum NetopiaStatus: int
{
    case New = 1;
    case Opened = 2;
    case Paid = 3;
    case Confirmed = 5;
    case Pending = 6;
    case Error = 11;
    case InvalidAccount = 12;
    case Fraud = 13;
    case PendingAuth = 14;
    case Awaiting3DS = 15;
    case PendingAny = 18;

    public static function labels(): array
    {
        return [
            self::New->value => 'new',
            self::Opened->value => 'opened',
            self::Paid->value => 'is paid',
            self::Confirmed->value => 'is confirmed',
            self::Pending->value => 'pending',
            self::Error->value => 'error',
            self::InvalidAccount->value => 'Invalid Account',
            self::Fraud->value => 'fraud',
            self::PendingAuth->value => 'pending authorization',
            self::Awaiting3DS->value => 'need authorize',
            self::PendingAny->value => 'pending',
        ];
    }

    public static function getLabelByStatus(int $status): string
    {
        return self::labels()[$status] ?? 'unknown';
    }
}
