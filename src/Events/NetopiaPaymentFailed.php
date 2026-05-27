<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;

class NetopiaPaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PaymentStatusDto $status)
    {}
}
