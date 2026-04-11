<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use MarianDumitru\Netopay\Contracts\NetopiaClientInterface;
use MarianDumitru\Netopay\Dto\OrderData;
use MarianDumitru\Netopay\Dto\IpnPayloadDto;
use MarianDumitru\Netopay\Dto\StartPaymentRequestDto;
use MarianDumitru\Netopay\Dto\StartPaymentResponseDto;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;
use MarianDumitru\Netopay\Dto\StartConfigDto;
use MarianDumitru\Netopay\Dto\StartOrderDto;
use MarianDumitru\Netopay\Dto\StartPaymentDto;

class Netopay
{
    public function __construct(
        private readonly NetopiaClientInterface $client,
    ) {
    }

    /**
     * Initiate a hosted-page payment. Netopia redirects the user to enter card details.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function start(OrderData $orderData): StartPaymentResponseDto
    {
        $posSignature = $this->posSignature();
        $orderDto     = StartOrderDto::fromOrderData($orderData, $posSignature);
        $configDto    = new StartConfigDto();
        $paymentDto   = StartPaymentDto::forHostedPage();
        $request      = StartPaymentRequestDto::build($orderDto, $configDto, $paymentDto);

        return $this->client->start($request);
    }

    /**
     * Initiate a recurring payment using a saved card token (merchant-initiated transaction).
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function startWithToken(OrderData $orderData, string $token): StartPaymentResponseDto
    {
        $posSignature = $this->posSignature();
        $orderDto     = StartOrderDto::fromOrderData($orderData, $posSignature);
        $configDto    = new StartConfigDto();
        $paymentDto   = StartPaymentDto::fromToken($token);
        $request      = StartPaymentRequestDto::build($orderDto, $configDto, $paymentDto);

        return $this->client->start($request);
    }

    /**
     * Retrieve the current status of a payment from Netopia.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function retrieveStatus(string $ntpId, string $orderId): PaymentStatusDto
    {
        return $this->client->retrieveStatus($ntpId, $orderId);
    }

    /**
     * Complete a 3DS authentication flow.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function verifyAuth(string $orderId, string $authenticationToken, string $ntpId, array $formData): PaymentStatusDto
    {
        return $this->client->verifyAuth($orderId, $authenticationToken, $ntpId, $formData);
    }

    /**
     * Parse a raw IPN payload into a PaymentStatusDto without calling the API.
     */
    public function handleIpn(array $body, array $headers = []): PaymentStatusDto
    {
        return $this->client->handleIpn(new IpnPayloadDto($body, $headers));
    }

    private function posSignature(): string
    {
        $sandbox = config('netopay.sandbox', true);

        return $sandbox
            ? config('netopay.sandbox_credentials.pos_signature', '')
            : config('netopay.live.pos_signature', '');
    }
}
