<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Awaiting3DS = 'awaiting_3ds';
    case Unknown = 'unknown';

    /**
     * Map a Netopia status code to a payment status.
     *
     * A code the package doesn't recognise, or a missing one (0), is Unknown,
     * never Failed: treating it as a failure could cancel a payment that later
     * succeeds.
     */
    public static function getStatus(int $providerStatus): self
    {
        return match (NetopiaStatus::tryFrom($providerStatus)) {
            NetopiaStatus::Paid => self::Paid,
            NetopiaStatus::Confirmed => self::Confirmed,
            NetopiaStatus::Awaiting3DS => self::Awaiting3DS,
            NetopiaStatus::New,
            NetopiaStatus::Opened,
            NetopiaStatus::Pending,
            NetopiaStatus::PendingAuth,
            NetopiaStatus::PendingAny => self::Pending,
            NetopiaStatus::Error,
            NetopiaStatus::InvalidAccount,
            NetopiaStatus::Fraud => self::Failed,
            null => self::Unknown,
        };
    }
}
