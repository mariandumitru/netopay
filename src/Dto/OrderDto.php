<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

readonly class OrderDto
{
    public function __construct(
        public string     $orderId,
        public float      $amount,
        public string     $currency,
        public string     $description,
        public BillingDto $billing,
        public bool       $merchantInitiated = false,
    ) {}
}
