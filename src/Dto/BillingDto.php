<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

readonly class BillingDto
{
    public function __construct(
        public string $email,
        public string $phone,
        public string $firstName,
        public string $lastName,
        public string $city,
        public int $country,
        public string $state,
        public string $postalCode,
        public string $details,
    ) {}

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'city' => $this->city,
            'country' => $this->country,
            'state' => $this->state,
            'postalCode' => $this->postalCode,
            'details' => $this->details,
        ];
    }
}
