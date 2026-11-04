<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

use MarianDumitru\Netopay\Enums\PaymentStatus;

class StartPaymentResponseDto
{
    public function __construct(
        public string $providerPaymentId,
        public int $providerStatusCode,
        public ?array $customerAction,
        public ?string $paymentUrl,
        public string $rawResponse,
        public ?string $token = null,
        public ?string $authCode = null,
        public ?string $rrn = null,
    ) {}

    public static function fromNetopiaStart(array $data): self
    {
        return new self(
            providerPaymentId: (string) ($data['payment']['ntpID'] ?? ''),
            providerStatusCode: (int) ($data['payment']['status'] ?? 0),
            customerAction: ! empty($data['customerAction']) ? $data['customerAction'] : null,
            paymentUrl: $data['payment']['paymentURL'] ?? null,
            rawResponse: json_encode($data),
            token: $data['payment']['token'] ?? null,
            authCode: $data['payment']['data']['AuthCode'] ?? null,
            rrn: $data['payment']['data']['RRN'] ?? null,
        );
    }

    public function state(): PaymentStatus
    {
        return PaymentStatus::getStatus($this->providerStatusCode);
    }
}
