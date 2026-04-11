<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

class StartOrderDto
{
    public function __construct(
        public string      $posSignature,
        public string      $orderId,
        public string      $description,
        public float       $amountMinor,
        public string      $currency,
        public BillingData $billing,
        public array       $products = [],
        public array       $installments = [],
        public ?string     $dateTimeIso = null,
        public array       $customData = [],
        public bool        $isMerchantInitiated = false,
    ) {
    }

    public static function fromOrderData(OrderData $data, string $posSignature): self
    {
        return new self(
            posSignature:        $posSignature,
            orderId:             $data->orderId,
            description:         $data->description,
            amountMinor:         $data->amount,
            currency:            $data->currency,
            billing:             $data->billing,
            products:            [],
            installments:        self::defaultInstallments(),
            dateTimeIso:         now()->toIso8601String(),
            customData:          [],
            isMerchantInitiated: $data->merchantInitiated,
        );
    }

    public function toArray(): array
    {
        $data = [
            'ntpID'        => '',
            'posSignature' => $this->posSignature,
            'dateTime'     => $this->dateTimeIso ?? now()->toIso8601String(),
            'description'  => $this->description,
            'orderID'      => $this->orderId,
            'amount'       => $this->amountMinor,
            'currency'     => $this->currency,
            'billing'      => $this->billing->toArray(),
            'shipping'     => $this->billing->toArray(),
            'installments' => $this->installments ?: self::defaultInstallments(),
        ];

        if ($this->isMerchantInitiated) {
            $data['scaExemptionInd'] = 'MIT';
        }

        if (!empty($this->products)) {
            $data['products'] = $this->products;
        }

        if (!empty($this->customData)) {
            $data['data'] = $this->customData;
        }

        return $data;
    }

    private static function defaultInstallments(): array
    {
        return [
            'selected'  => 0,
            'available' => [0],
        ];
    }
}
