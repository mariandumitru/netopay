<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

class StartPaymentResponseDto
{
    public function __construct(
        public string  $providerPaymentId,
        public int     $providerStatusCode,
        public ?array  $customerAction,
        public ?string $paymentUrl,
        public string  $rawResponse,
        public ?string $token = null,
    ) {
    }

    public static function fromNetopiaStart(array $data): self
    {
        return new self(
            providerPaymentId:  (string) ($data['payment']['ntpID'] ?? ''),
            providerStatusCode: (int) ($data['payment']['status'] ?? 0),
            customerAction:     !empty($data['customerAction']) ? $data['customerAction'] : null,
            paymentUrl:         $data['payment']['paymentURL'] ?? null,
            rawResponse:        json_encode($data),
            token:              $data['payment']['token'] ?? null,
        );
    }
}
