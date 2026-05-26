<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MarianDumitru\Netopay\Contracts\NetopiaClientInterface;
use MarianDumitru\Netopay\Dto\IpnPayloadDto;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;
use MarianDumitru\Netopay\Dto\StartPaymentRequestDto;
use MarianDumitru\Netopay\Dto\StartPaymentResponseDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;

readonly class NetopiaClient implements NetopiaClientInterface
{
    public function __construct(
        private string $apiKey,
        private string $startEndpoint,
        private string $statusEndpoint,
        private string $verifyAuthEndpoint,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function start(StartPaymentRequestDto $requestPayload): StartPaymentResponseDto
    {
        Log::debug('Netopia start payment request', [
            'payload' => $requestPayload->toArray(),
        ]);

        $response = Http::withHeaders($this->authorizationHeaders())
            ->asJson()
            ->post($this->startEndpoint, $requestPayload->toArray());

        $response->throw();

        $data = $response->json();

        Log::debug('Netopia start payment response', ['response' => $data]);

        return StartPaymentResponseDto::fromNetopiaStart($data);
    }

    public function handleIpn(IpnPayloadDto $ipnPayload): PaymentStatusDto
    {
        $providerStatus = (int) ($ipnPayload->body['payment']['status'] ?? 0);
        $status = PaymentStatus::getStatus($providerStatus);
        $payload = $ipnPayload->body;
        $payload['status'] = $status;

        Log::debug('Netopia IPN received', ['payload' => $ipnPayload->body]);

        return PaymentStatusDto::fromIpnPayload($payload);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function retrieveStatus(string $ntpId, string $orderId): PaymentStatusDto
    {
        $response = Http::withHeaders($this->authorizationHeaders())
            ->timeout(5)
            ->asJson()
            ->post($this->statusEndpoint, ['ntpID' => $ntpId, 'orderID' => $orderId]);

        $response->throw();

        $data = $response->json();
        $providerStatus = (int) ($data['payment']['status'] ?? 0);
        $status = PaymentStatus::getStatus($providerStatus);
        $data['status'] = $status;

        Log::debug('Netopia retrieve status response', ['response' => $data]);

        return PaymentStatusDto::fromNetopiaStatus($data);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function verifyAuth(string $orderId, string $authenticationToken, string $ntpId, array $formData): PaymentStatusDto
    {
        $response = Http::withHeaders($this->authorizationHeaders())
            ->timeout(5)
            ->asJson()
            ->post($this->verifyAuthEndpoint, [
                'authenticationToken' => $authenticationToken,
                'ntpID' => $ntpId,
                'formData' => $formData,
            ]);

        $response->throw();

        $data = $response->json();
        $providerStatus = (int) ($data['payment']['status'] ?? 0);
        $state = PaymentStatus::getStatus($providerStatus);
        $data['status'] = $state;
        $data['order']['orderID'] = $orderId;

        Log::debug('Netopia verify auth response', ['response' => $data]);

        return PaymentStatusDto::fromNetopiaVerifyAuth($data);
    }

    private function authorizationHeaders(): array
    {
        return ['Authorization' => $this->apiKey];
    }
}
