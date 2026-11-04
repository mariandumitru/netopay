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
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case Chargeback = 'chargeback';
    case Unknown = 'unknown';

    /**
     * Map a Netopia status code to a payment status.
     *
     * A code the package doesn't map, or a missing one (0), is Unknown, never
     * Failed: treating it as a failure could cancel a payment that later
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
            NetopiaStatus::PendingAny,
            NetopiaStatus::Fraud => self::Pending,
            NetopiaStatus::Error,
            NetopiaStatus::InvalidAccount => self::Failed,
            NetopiaStatus::Canceled => self::Cancelled,
            NetopiaStatus::Expired => self::Expired,
            NetopiaStatus::Credit,
            NetopiaStatus::Reversed => self::Refunded,
            NetopiaStatus::ChargebackInit,
            NetopiaStatus::ChargebackAccept,
            NetopiaStatus::ChargebackRepresentment => self::Chargeback,
            NetopiaStatus::Scheduled,
            NetopiaStatus::ProgrammedRecurrentPayment,
            NetopiaStatus::CanceledProgrammedRecurrentPayment,
            NetopiaStatus::TrialPending,
            NetopiaStatus::Trial,
            null => self::Unknown,
        };
    }
}
