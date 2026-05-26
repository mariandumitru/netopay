<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

readonly class StartPaymentRequestDto
{
    public function __construct(
        private StartConfigDto $config,
        private StartOrderDto $order,
        private StartPaymentDto $payment,
    ) {}

    public static function build(StartOrderDto $order, StartConfigDto $config, StartPaymentDto $payment): self
    {
        return new self(config: $config, order: $order, payment: $payment);
    }

    public function toArray(): array
    {
        return [
            'config' => $this->config->toArray(),
            'payment' => $this->payment->toArray(),
            'order' => $this->order->toArray(),
        ];
    }
}
