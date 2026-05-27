<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NetopiaReturnReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $orderId,
        public array $formData,
        public array $headers
    ) {}
}
