<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NetopiaIpnProcessingFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Throwable $exception,
        public readonly array $payload,
        public readonly array $headers,
    ) {}
}
