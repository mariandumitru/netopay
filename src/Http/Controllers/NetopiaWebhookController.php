<?php

declare(strict_types=1);

namespace MarianDumitru\Netopay\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use MarianDumitru\Netopay\Contracts\NetopiaClientInterface;
use MarianDumitru\Netopay\Dto\IpnPayloadDto;
use MarianDumitru\Netopay\Dto\PaymentStatusDto;
use MarianDumitru\Netopay\Enums\PaymentStatus;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;
use MarianDumitru\Netopay\Events\NetopiaPaymentFailed;
use MarianDumitru\Netopay\Events\NetopiaPaymentPending;
use MarianDumitru\Netopay\Events\NetopiaReturnReceived;
use Throwable;

class NetopiaWebhookController extends Controller
{
    public function ipn(NetopiaClientInterface $netopiaClient, Request $request): Response
    {
        $body    = $request->all();
        $headers = $request->headers->all();

        try {
            $ipnStatus    = $netopiaClient->handleIpn(new IpnPayloadDto($body, $headers));
            $confirmedStatus = $netopiaClient->retrieveStatus($ipnStatus->providerPaymentId, $ipnStatus->orderId);

            $this->dispatchPaymentEvent($confirmedStatus);
        } catch (Throwable $e) {
            Log::error('Netopia IPN processing failed', [
                'error'   => $e->getMessage(),
                'payload' => $body,
            ]);
        }

        return response()->noContent();
    }

    public function return(Request $request): RedirectResponse
    {
        $orderId  = $request->query('orderId') ?? $request->input('orderId', '');
        $formData = $request->except('orderId');

        NetopiaReturnReceived::dispatch((string) $orderId, $formData, $request->headers->all());

        $redirect = config('netopay.after_payment_redirect', '/');

        return redirect($redirect);
    }

    private function dispatchPaymentEvent(PaymentStatusDto $status): void
    {
        match ($status->state) {
            PaymentStatus::Paid, PaymentStatus::Confirmed => NetopiaPaymentApproved::dispatch($status),
            PaymentStatus::Awaiting3DS => NetopiaPaymentPending::dispatch($status),
            default => NetopiaPaymentFailed::dispatch($status),
        };
    }
}
