<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Contracts;

use MarianDumitru\Netopay\Dto\IpnPayloadDto;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;
use MarianDumitru\Netopay\Dto\StartPaymentRequestDto;
use MarianDumitru\Netopay\Dto\StartPaymentResponseDto;

interface NetopiaClientInterface
{
    public function start(StartPaymentRequestDto $requestPayload): StartPaymentResponseDto;

    public function handleIpn(IpnPayloadDto $ipnPayload): PaymentStatusDto;

    public function retrieveStatus(string $ntpId, string $orderId): PaymentStatusDto;

    public function verifyAuth(string $orderId, string $authenticationToken, string $ntpId, array $formData): PaymentStatusDto;
}
