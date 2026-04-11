<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

readonly class IpnPayloadDto
{
    public function __construct(
        public array $body,
        public array $headers,
    ) {
    }
}
