<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;

/**
 * Fired when Netopia reports a payment as credited (8) or reversed (17).
 * The order may already be marked paid in the consuming app.
 */
class NetopiaPaymentRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentStatusDto $status,
        public readonly array $ipnPayload = [],
    ) {}
}
