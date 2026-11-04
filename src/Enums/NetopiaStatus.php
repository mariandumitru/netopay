<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Enums;

/**
 * Netopia payment status codes.
 *
 * NETOPIA's v2 docs list only 3, 5, 12 and 15. The full list comes from the
 * status constants in NETOPIA's official v2 PHP SDK (netopia/payment2,
 * src/IPN.php). Scheduled (7) and the recurrent/trial codes (19-22) are SMS
 * and subscription statuses that card payments don't use.
 */
enum NetopiaStatus: int
{
    case New = 1;
    case Opened = 2;
    case Paid = 3;
    case Canceled = 4;
    case Confirmed = 5;
    case Pending = 6;
    case Scheduled = 7;
    case Credit = 8;
    case ChargebackInit = 9;
    case ChargebackAccept = 10;
    case Error = 11;
    case InvalidAccount = 12;
    /**
     * Flagged for fraud review. NETOPIA's SDK treats it as a review hold
     * ("payment in reviewing"), not a final decline: the payment can still
     * be approved or refused after the review.
     */
    case Fraud = 13;
    case PendingAuth = 14;
    case Awaiting3DS = 15;
    case ChargebackRepresentment = 16;
    case Reversed = 17;
    case PendingAny = 18;
    case ProgrammedRecurrentPayment = 19;
    case CanceledProgrammedRecurrentPayment = 20;
    case TrialPending = 21;
    case Trial = 22;
    case Expired = 23;

    /**
     * The SDK calls 12 "declined"; the v2 docs call it "Invalid Account".
     */
    public const Declined = self::InvalidAccount;

    public static function labels(): array
    {
        return [
            self::New->value => 'new',
            self::Opened->value => 'opened',
            self::Paid->value => 'is paid',
            self::Canceled->value => 'canceled',
            self::Confirmed->value => 'is confirmed',
            self::Pending->value => 'pending',
            self::Scheduled->value => 'scheduled',
            self::Credit->value => 'credit (refunded)',
            self::ChargebackInit->value => 'chargeback initiated',
            self::ChargebackAccept->value => 'chargeback accepted',
            self::Error->value => 'error',
            self::InvalidAccount->value => 'declined (invalid account)',
            self::Fraud->value => 'payment in review',
            self::PendingAuth->value => 'pending authorization',
            self::Awaiting3DS->value => 'need authorize',
            self::ChargebackRepresentment->value => 'chargeback representment',
            self::Reversed->value => 'reversed',
            self::PendingAny->value => 'pending',
            self::ProgrammedRecurrentPayment->value => 'programmed recurrent payment',
            self::CanceledProgrammedRecurrentPayment->value => 'canceled programmed recurrent payment',
            self::TrialPending->value => 'trial pending',
            self::Trial->value => 'trial',
            self::Expired->value => 'expired',
        ];
    }

    public static function getLabelByStatus(int $status): string
    {
        return self::labels()[$status] ?? 'unknown';
    }
}
