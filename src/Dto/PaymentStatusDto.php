<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

use MarianDumitru\Netopay\Enums\PaymentStatus;

readonly class PaymentStatusDto
{
    public function __construct(
        public string $orderId,
        public string $providerPaymentId,
        public PaymentStatus $state,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?string $authCode = null,
        public ?string $rrn = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $paymentToken = null,
        public ?string $raw = null,
        public ?int $providerStatusCode = null,
    ) {}

    public static function fromIpnPayload(array $payload): self
    {
        return new self(
            orderId: (string) ($payload['order']['orderID'] ?? ''),
            providerPaymentId: (string) ($payload['payment']['ntpID'] ?? ''),
            state: $payload['status'],
            errorCode: $payload['error']['code'] ?? null,
            errorMessage: $payload['error']['message'] ?? null,
            authCode: $payload['auth_code'] ?? null,
            rrn: $payload['rrn'] ?? null,
            amount: isset($payload['payment']['amount']) ? (float) $payload['payment']['amount'] : null,
            currency: $payload['payment']['currency'] ?? $payload['order']['currency'] ?? config('netopay.currency', 'RON'),
            raw: json_encode($payload),
            providerStatusCode: self::statusCode($payload),
        );
    }

    public static function fromNetopiaStatus(array $data): self
    {
        return new self(
            orderId: (string) ($data['order']['orderID'] ?? ''),
            providerPaymentId: (string) ($data['payment']['ntpID'] ?? ''),
            state: $data['status'],
            errorCode: $data['error']['code'] ?? null,
            errorMessage: $data['error']['message'] ?? null,
            authCode: null,
            rrn: $data['payment']['rrn'] ?? null,
            amount: isset($data['payment']['amount']) ? (float) $data['payment']['amount'] : null,
            currency: $data['payment']['currency'] ?? $data['order']['currency'] ?? config('netopay.currency', 'RON'),
            paymentToken: $data['payment']['token'] ?? null,
            raw: json_encode($data),
            providerStatusCode: self::statusCode($data),
        );
    }

    public static function fromNetopiaVerifyAuth(array $data): self
    {
        return new self(
            orderId: (string) ($data['order']['orderID'] ?? ''),
            providerPaymentId: (string) ($data['payment']['ntpID'] ?? ''),
            state: $data['status'],
            errorCode: $data['error']['code'] ?? null,
            errorMessage: $data['error']['message'] ?? null,
            authCode: $data['payment']['data']['AuthCode'] ?? null,
            rrn: $data['payment']['data']['RRN'] ?? null,
            amount: isset($data['payment']['amount']) ? (float) $data['payment']['amount'] : null,
            currency: $data['payment']['currency'] ?? $data['order']['currency'] ?? config('netopay.currency', 'RON'),
            paymentToken: $data['payment']['token'] ?? null,
            raw: json_encode($data),
            providerStatusCode: self::statusCode($data),
        );
    }

    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'providerPaymentId' => $this->providerPaymentId,
            'state' => $this->state,
            'errorCode' => $this->errorCode,
            'errorMessage' => $this->errorMessage,
            'authCode' => $this->authCode,
            'rrn' => $this->rrn,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'paymentToken' => $this->paymentToken,
            'raw' => $this->raw,
            'providerStatusCode' => $this->providerStatusCode,
        ];
    }

    /**
     * The raw Netopia status code, or null when the response has none.
     */
    private static function statusCode(array $data): ?int
    {
        $code = $data['payment']['status'] ?? null;

        return is_numeric($code) ? (int) $code : null;
    }
}
