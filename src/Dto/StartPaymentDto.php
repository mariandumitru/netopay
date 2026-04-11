<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Dto;

class StartPaymentDto
{
    public function __construct(
        public array $options,
        public array $instrument,
        public array $data,
    ) {
    }

    public static function forHostedPage(): self
    {
        return new self(
            options:    ['installments' => 0, 'bonus' => 0],
            instrument: ['type' => 'card'],
            data:       [],
        );
    }

    public static function fromToken(string $token): self
    {
        return new self(
            options:    [],
            instrument: ['type' => 'card', 'token' => $token],
            data:       [],
        );
    }

    public function toArray(): array
    {
        $result = ['instrument' => $this->instrument];

        if (!empty($this->options)) {
            $result['options'] = $this->options;
        }

        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }

        return $result;
    }
}
