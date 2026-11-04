<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;

/**
 * Fired when Netopia reports a chargeback: initiated (9), accepted (10) or
 * representment (16). Read $status->providerStatusCode to tell them apart.
 * The order may already be marked paid in the consuming app.
 */
class NetopiaPaymentChargeback
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentStatusDto $status,
        public readonly array $ipnPayload = [],
    ) {}
}
