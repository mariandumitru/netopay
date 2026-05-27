# Netopay — Laravel package for NETOPIA Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mariandumitru/netopay.svg?style=flat-square)](https://packagist.org/packages/mariandumitru/netopay)
[![Total Downloads](https://img.shields.io/packagist/dt/mariandumitru/netopay.svg?style=flat-square)](https://packagist.org/packages/mariandumitru/netopay)
[![License](https://img.shields.io/packagist/l/mariandumitru/netopay.svg?style=flat-square)](LICENSE.md)

A Laravel package for integrating with the [NETOPIA Payments](https://netopia-payments.com) API. Supports hosted-page card payments, recurring payments via saved card tokens, IPN webhook handling, 3DS authentication, and payment status retrieval.

The package handles all HTTP communication with Netopia and fires **Laravel events** your application listens to — keeping your business logic completely separate from the payment protocol.

---

## Quick start

The happy path from `composer require` to a working sandbox payment, in five steps. Each step has a full section further down.

**1. Install and publish the config**

```bash
composer require mariandumitru/netopay
php artisan vendor:publish --tag=netopay-config
```

**2. Set the credentials** in `.env` (sandbox is the default):

```env
NETOPIA_API_KEY_SANDBOX=your-real-sandbox-api-key
NETOPIA_POS_SIGNATURE_SANDBOX=your-real-sandbox-pos-signature
NETOPIA_AFTER_PAYMENT_REDIRECT=/dashboard
```

**3. Exclude the package routes from CSRF** in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['netopia/ipn', 'netopia/return']);
})
```

**4. Start a payment** from your own controller:

```php
use MarianDumitru\Netopay\Facades\Netopay;

$response = Netopay::start($orderData);

return redirect($response->paymentUrl); // see XHR note in section 1 below if you're on Inertia/Livewire
```

**5. Listen for the outcome** in `App\Providers\AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Event;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;

Event::listen(NetopiaPaymentApproved::class, function ($event) {
    // fulfil the order — see "Handling payment outcomes" for the full pattern
});
```

That's the whole picture. The rest of this README expands on each step (building `$orderData`, the double-fulfilment guard, 3DS, recurring payments, testing).

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

> The package is verified against Laravel 13 in CI. Laravel 11 and 12 are supported based on code audit (no L13-specific APIs are used); if you hit an issue on those versions, please open an issue.

---

## Installation

```bash
composer require mariandumitru/netopay
```

The service provider and `Netopay` facade are registered automatically via Laravel package discovery.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=netopay-config
```

---

## Configuration

Add the following to your `.env` file:

```env
NETOPIA_SANDBOX=true

# Sandbox credentials
NETOPIA_API_KEY_SANDBOX=your-sandbox-api-key
NETOPIA_POS_SIGNATURE_SANDBOX=your-sandbox-pos-signature

# Live credentials
NETOPIA_API_KEY_LIVE=your-live-api-key
NETOPIA_POS_SIGNATURE_LIVE=your-live-pos-signature

# Optional — if unset, the package auto-resolves these to its own routes
# (named `netopia.ipn` and `netopia.return`). Set them explicitly only if
# you've disabled the package routes or you want to override the URLs.
# NETOPIA_NOTIFY_URL=https://yourdomain.com/netopia/ipn
# NETOPIA_REDIRECT_URL=https://yourdomain.com/netopia/return

# Where your app redirects the user after processing the return
NETOPIA_AFTER_PAYMENT_REDIRECT=/dashboard

# Optional — payment defaults
# NETOPIA_CURRENCY=RON
# NETOPIA_LANGUAGE=ro
# NETOPIA_EMAIL_TEMPLATE=confirm
```

Set `NETOPIA_SANDBOX=false` in production. The package automatically switches API keys and endpoints based on this value.

### Full environment variable reference

| Variable | Default | Description |
|---|---|---|
| `NETOPIA_SANDBOX` | `true` | Switches all credentials and endpoints between sandbox and live. |
| `NETOPIA_API_KEY_SANDBOX` | — | Sandbox API key. |
| `NETOPIA_POS_SIGNATURE_SANDBOX` | falls back to `NETOPIA_SALES_POINT_KEY` | Sandbox POS signature. |
| `NETOPIA_API_KEY_LIVE` | — | Live API key. |
| `NETOPIA_POS_SIGNATURE_LIVE` | falls back to `NETOPIA_SALES_POINT_KEY` | Live POS signature. |
| `NETOPIA_SALES_POINT_KEY` | — | Shared fallback used by *both* `NETOPIA_POS_SIGNATURE_SANDBOX` and `NETOPIA_POS_SIGNATURE_LIVE` when those are unset. Useful when migrating from older integrations, but be aware it applies the same value to both environments. |
| `NETOPIA_NOTIFY_URL` | auto → `route('netopia.ipn')` | URL Netopia POSTs the IPN to. Optional when package routes are enabled. |
| `NETOPIA_REDIRECT_URL` | auto → `route('netopia.return')` | URL Netopia redirects the user to after payment. Optional when package routes are enabled. |
| `NETOPIA_AFTER_PAYMENT_REDIRECT` | `/` | Where the package's return controller redirects the user after firing `NetopiaReturnReceived`. |
| `NETOPIA_CURRENCY` | `RON` | Fallback currency when Netopia's response omits one. |
| `NETOPIA_LANGUAGE` | `ro` | Language of the hosted payment page Netopia shows the customer (`ro`, `en`, …). |
| `NETOPIA_EMAIL_TEMPLATE` | `confirm` | Email template identifier sent to Netopia on payment start. |
| `NETOPIA_API_URL_SANDBOX` | sandbox `/payment/card/start` URL | Override the sandbox start endpoint (rarely needed). |
| `NETOPIA_STATUS_URL_SANDBOX` | sandbox `/operation/status` URL | Override the sandbox status endpoint. |
| `NETOPIA_VERIFY_AUTH_URL_SANDBOX` | sandbox `/payment/card/verify-auth` URL | Override the sandbox 3DS verify endpoint. |
| `NETOPIA_API_URL_LIVE` | live `/payment/card/start` URL | Override the live start endpoint. |
| `NETOPIA_STATUS_URL_LIVE` | live `/operation/status` URL | Override the live status endpoint. |
| `NETOPIA_VERIFY_AUTH_URL_LIVE` | live `/payment/card/verify-auth` URL | Override the live 3DS verify endpoint. |

> **About `NETOPIA_NOTIFY_URL` / `NETOPIA_REDIRECT_URL`.** If you set these explicitly, they must point at the package's actual webhook routes — `<your-app-url>/netopia/ipn` and `<your-app-url>/netopia/return` by default. If you changed `routes.prefix` in `config/netopay.php` (e.g. to `payments`), the URLs become `<your-app-url>/payments/ipn` and `<your-app-url>/payments/return`. The endpoint paths inside the prefix (`/ipn`, `/return`) are fixed by the package.

### Routes

The package registers two routes automatically:

| Method | URI | Description |
|---|---|---|
| `POST` | `/netopia/ipn` | Netopia IPN webhook |
| `GET\|POST` | `/netopia/return` | User return after payment |

> **Important:** Exclude these routes from CSRF verification in your `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'netopia/ipn',
        'netopia/return',
    ]);
})
```

You can disable the package routes entirely and register your own:

```php
// config/netopay.php
'routes' => [
    'enabled'    => false,
    'prefix'     => 'netopia',
    'middleware' => [],
],
```

> **Heads up:** when package routes are disabled, the auto-resolution of `NETOPIA_NOTIFY_URL` / `NETOPIA_REDIRECT_URL` relies on the named routes `netopia.ipn` and `netopia.return`. Either preserve those route names on your own routes, or set both env vars explicitly — otherwise `Netopay::start()` throws `RouteNotFoundException`.

---

## Usage

### 1. Initiating a payment

Build an `OrderDto` from your application's data and call `Netopay::start()`. This returns a `StartPaymentResponseDto` containing the Netopia-hosted page URL to redirect the user to.

```php
use MarianDumitru\Netopay\Dto\BillingDto;
use MarianDumitru\Netopay\Dto\OrderDto;
use MarianDumitru\Netopay\Facades\Netopay;

$billing = new BillingDto(
    email:      $user->email,
    phone:      $billingProfile->phone,
    firstName:  $user->first_name,
    lastName:   $user->last_name,
    city:       $billingProfile->city,
    country:    $billingProfile->numericCountryCode, // ISO 3166-1 numeric (e.g. 642 for Romania)
    state:      $billingProfile->state,
    postalCode: $billingProfile->post_code,
    details:    $billingProfile->full_address,
);

$orderData = new OrderDto(
    orderId:     $payment->uuid,   // your unique order identifier
    amount:      149.99,
    currency:    'RON',
    description: 'Subscription — 2 devices (Monthly)',
    billing:     $billing,
);

$response = Netopay::start($orderData);

// Persist what you'll need later (ntpID, and the 3DS authenticationToken if present)
// so your return listener can look the payment up and verify 3DS.
$payment->update([
    'provider_payment_id' => $response->providerPaymentId,
    'payload'             => [
        'start' => [
            'customerAction' => $response->customerAction, // contains authenticationToken when 3DS is required
        ],
    ],
]);

// Redirect the user to Netopia's hosted payment page
return redirect($response->paymentUrl);
```

> The `payload` column above is just a JSON column on your `payments` table — name it whatever fits your schema. The point is to keep `$response->customerAction` somewhere your `NetopiaReturnReceived` listener can read it, since 3DS verification needs the `authenticationToken`.

> **XHR / SPA gotcha.** A plain `redirect($response->paymentUrl)` returns a `302` to the browser. If you initiate the payment via XHR (Axios, Fetch, Inertia `router.post`, Livewire `wire:click`), the XHR layer **cannot follow a cross-origin redirect** to Netopia's domain — the user appears stuck and nothing happens. Use the SPA-appropriate redirect instead:
>
> ```php
> // Inertia
> return Inertia::location($response->paymentUrl);
>
> // Livewire (v3)
> return $this->redirect($response->paymentUrl, navigate: false);
>
> // Axios / Fetch — return the URL as JSON and redirect on the client
> return response()->json(['paymentUrl' => $response->paymentUrl]);
> // then on the front-end: window.location.href = data.paymentUrl;
> ```

### 2. Handling payment outcomes

The package fires Laravel events from its webhook controller. Register listeners in your `App\Providers\AppServiceProvider::boot()` method (Laravel 11+ no longer ships an `EventServiceProvider`):

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Event;
use MarianDumitru\Netopay\Events\NetopiaIpnProcessingFailed;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;
use MarianDumitru\Netopay\Events\NetopiaPaymentFailed;
use MarianDumitru\Netopay\Events\NetopiaPaymentPending;
use MarianDumitru\Netopay\Events\NetopiaReturnReceived;

public function boot(): void
{
    Event::listen(NetopiaPaymentApproved::class, HandlePaymentApproved::class);
    Event::listen(NetopiaPaymentFailed::class, HandlePaymentFailed::class);
    Event::listen(NetopiaPaymentPending::class, HandlePaymentPending::class);
    Event::listen(NetopiaReturnReceived::class, HandleNetopiaReturn::class);
    Event::listen(NetopiaIpnProcessingFailed::class, ReportIpnFailure::class);
}
```

#### NetopiaPaymentApproved

Fired by the IPN controller when Netopia confirms a `Paid` or `Confirmed` status. This is where you fulfil the order.

> **Double-fulfilment guard.** The IPN webhook and the user-facing return redirect can both arrive within a few seconds of each other and may both run your fulfilment logic. Wrap the lookup in a transaction with `lockForUpdate()` and check the status before acting, so only one of the two paths fulfils.

```php
use Illuminate\Support\Facades\DB;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;

class HandlePaymentApproved
{
    public function handle(NetopiaPaymentApproved $event): void
    {
        $status = $event->status; // PaymentStatusDto

        DB::transaction(function () use ($status) {
            $payment = Payment::where('uuid', $status->orderId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status === 'paid') {
                return; // already fulfilled by the other path
            }

            $payment->update([
                'status'              => 'paid',
                'provider_payment_id' => $status->providerPaymentId,
                'auth_code'           => $status->authCode,
                'rrn'                 => $status->rrn,
                'paid_at'             => now(),
            ]);

            if ($status->paymentToken) {
                PaymentToken::updateOrCreate(
                    ['user_id' => $payment->user_id],
                    ['token'   => $status->paymentToken],
                );
            }

            SubscriptionService::fulfil($payment);
        });
    }
}
```

#### NetopiaIpnProcessingFailed

Fired when the IPN controller cannot parse or confirm an inbound IPN (network error from Netopia, malformed payload, etc.). The controller still returns HTTP 204 to Netopia — this event is your hook to alert ops, retry via a queue, or push a breadcrumb to Sentry.

```php
use MarianDumitru\Netopay\Events\NetopiaIpnProcessingFailed;

class ReportIpnFailure
{
    public function handle(NetopiaIpnProcessingFailed $event): void
    {
        report($event->exception); // send to Sentry / your reporter

        Log::warning('Netopia IPN failed', [
            'order_id' => $event->payload['order']['orderID'] ?? null,
            'ntp_id'   => $event->payload['payment']['ntpID'] ?? null,
        ]);
    }
}
```

#### NetopiaReturnReceived

Fired when the user is redirected back to your application after completing (or abandoning) payment. Use this to look up the payment status and update your UI. For hosted-page flows, call `Netopay::retrieveStatus()`. For 3DS flows, call `Netopay::verifyAuth()` first if an auth token is present.

`$event->formData` is the raw POST/query payload Netopia sent on return (everything except `orderId`) as an `array<string, mixed>`. For 3DS flows, pass it straight through to `Netopay::verifyAuth()`.

```php
use MarianDumitru\Netopay\Events\NetopiaReturnReceived;
use MarianDumitru\Netopay\Facades\Netopay;

class HandleNetopiaReturn
{
    public function handle(NetopiaReturnReceived $event): void
    {
        if ($event->orderId === '') {
            // The package already logs a warning. Decide here whether to alert or silently drop.
            return;
        }

        $payment = Payment::where('uuid', $event->orderId)->first();

        if (! $payment) {
            return;
        }

        // Set during the start call — see "Initiating a payment" above
        $authToken = data_get($payment->payload, 'start.customerAction.authenticationToken');

        if ($authToken) {
            // 3DS flow: verify authentication first
            $result = Netopay::verifyAuth(
                $event->orderId,
                $authToken,
                $payment->provider_payment_id,
                $event->formData,
            );
        } else {
            // Hosted-page flow: retrieve confirmed status
            $result = Netopay::retrieveStatus(
                $payment->provider_payment_id,
                $event->orderId,
            );
        }

        $payment->update(['status' => $result->state->value]);
    }
}
```

> **Note:** The IPN webhook (`NetopiaPaymentApproved`) and the return redirect (`NetopiaReturnReceived`) may arrive concurrently. See the double-fulfilment guard pattern in the `NetopiaPaymentApproved` listener above — apply the same `DB::transaction` + `lockForUpdate` approach in any code path that mutates payment state.

### 3. Recurring payments with a saved card token

Once a user has paid and you have saved their card token, use `startWithToken()` for merchant-initiated renewals. No redirect is needed — the payment is processed immediately.

```php
$response = Netopay::startWithToken($orderData, $savedToken);

if ($response->providerStatusCode === 3 || $response->providerStatusCode === 5) {
    // Payment approved — fulfil the order
}
```

### 4. Retrieving payment status manually

```php
$status = Netopay::retrieveStatus($ntpId, $orderId);

echo $status->state->value;    // 'paid', 'confirmed', 'failed', etc.
echo $status->authCode;
echo $status->rrn;
echo $status->paymentToken;
```

### 5. Landing the user on the just-paid resource

`NETOPIA_AFTER_PAYMENT_REDIRECT` is a static URL — fine for "always go to `/dashboard`," but most apps want to land the user on the specific resource they paid for (e.g. `/orders/123`). The pattern is to stash the post-payment URL in the session before redirecting to Netopia, then pull it out in your `NetopiaReturnReceived` listener and use it instead of the static config.

**At start time** (your controller, just before redirecting to Netopia):

```php
session()->put(
    'netopay.post_payment_redirect.' . $payment->uuid,
    route('orders.show', $order),
);

return redirect($response->paymentUrl);
```

**On return** (your listener), override the package's static redirect by returning your own response. Because the package's return controller has already redirected to `NETOPIA_AFTER_PAYMENT_REDIRECT` by the time your listener runs, the cleanest pattern is to point `NETOPIA_AFTER_PAYMENT_REDIRECT` at an intermediate route in your app — `/payments/landing` — and resolve the real destination there:

```php
// routes/web.php
Route::get('/payments/landing', function () {
    // The orderId arrives via NetopiaReturnReceived; the listener can stash it on the session
    // under 'netopay.last_order_id' so this intermediate route knows where to go.
    $orderId = session()->pull('netopay.last_order_id');
    $url     = session()->pull("netopay.post_payment_redirect.{$orderId}", '/dashboard');

    return redirect($url);
});
```

```php
// HandleNetopiaReturn listener
public function handle(NetopiaReturnReceived $event): void
{
    session()->put('netopay.last_order_id', $event->orderId);

    // ... the rest of your verifyAuth / retrieveStatus logic
}
```

Set `NETOPIA_AFTER_PAYMENT_REDIRECT=/payments/landing` in `.env` and every payment lands the user on the right page.

---

## Events Reference

| Event | Property | Type | Description |
|---|---|---|---|
| `NetopiaPaymentApproved` | `$status` | `PaymentStatusDto` | Payment is `Paid` (3) or `Confirmed` (5) |
| `NetopiaPaymentPending` | `$status` | `PaymentStatusDto` | Payment is awaiting 3DS (status 15) |
| `NetopiaPaymentFailed` | `$status` | `PaymentStatusDto` | Payment failed or was declined |
| `NetopiaIpnProcessingFailed` | `$exception` | `Throwable` | Exception thrown while handling the IPN |
| | `$payload` | `array` | Raw IPN body received from Netopia |
| | `$headers` | `array` | Raw IPN headers |
| `NetopiaReturnReceived` | `$orderId` | `string` | Your order identifier from the return URL — **empty string if Netopia did not send `orderId`**, in which case the package also logs a warning |
| | `$formData` | `array<string, mixed>` | POST/query data Netopia sent (everything except `orderId`). Pass straight to `Netopay::verifyAuth()` for 3DS flows |
| | `$headers` | `array` | Request headers |

### `PaymentStatusDto` properties

| Property | Type | Description |
|---|---|---|
| `$orderId` | `string` | Your order ID |
| `$providerPaymentId` | `string` | Netopia's `ntpID` |
| `$state` | `PaymentStatus` | Enum: `Paid`, `Confirmed`, `Failed`, `Awaiting3DS`, `Pending` |
| `$authCode` | `?string` | Authorization code (available after verify-auth) |
| `$rrn` | `?string` | Retrieval Reference Number |
| `$amount` | `?float` | Amount charged |
| `$currency` | `?string` | Currency code |
| `$paymentToken` | `?string` | Saved card token for future recurring payments |
| `$errorCode` | `?string` | Netopia error code |
| `$errorMessage` | `?string` | Netopia error message |
| `$raw` | `?string` | Full raw JSON response from Netopia |

---

## Facade API

```php
use MarianDumitru\Netopay\Facades\Netopay;

// Initiate a hosted-page payment
Netopay::start(OrderDto $orderData): StartPaymentResponseDto

// Initiate a merchant-initiated recurring payment
Netopay::startWithToken(OrderDto $orderData, string $token): StartPaymentResponseDto

// Retrieve confirmed status from Netopia
Netopay::retrieveStatus(string $ntpId, string $orderId): PaymentStatusDto

// Complete a 3DS authentication
Netopay::verifyAuth(string $orderId, string $authToken, string $ntpId, array $formData): PaymentStatusDto

// Parse a raw IPN body without an API call (used internally)
Netopay::handleIpn(array $body, array $headers = []): PaymentStatusDto
```

---

## Troubleshooting

### See the raw requests and responses

`NetopiaClient` logs every Netopia API request and response at the `debug` level (`Log::debug`). To see them, set:

```env
LOG_LEVEL=debug
```

You'll then see entries like `Netopia start payment request`, `Netopia start payment response`, `Netopia IPN received`, and `Netopia retrieve status response` in your log channel. Failures are logged at `error` — search for `Netopia IPN processing failed`.

### Netopia replies with "99 POS not found" or "POS inactive"

Your `NETOPIA_POS_SIGNATURE_SANDBOX` / `NETOPIA_POS_SIGNATURE_LIVE` is wrong, empty, or still set to the README placeholder. Common causes:

- The env var is unset or empty.
- The value is still `your-sandbox-pos-signature` from the example block — copy-paste leftover.
- The sandbox signature was used in production (or vice versa) — check `NETOPIA_SANDBOX`.
- Both `NETOPIA_POS_SIGNATURE_*` and `NETOPIA_SALES_POINT_KEY` are set, and the fallback is masking the value you think is being used. Remove `NETOPIA_SALES_POINT_KEY` to make the resolution explicit.

Verify the signature in your Netopia merchant dashboard and re-run the call with `LOG_LEVEL=debug` to inspect the exact request payload (`Netopia start payment request`).

### Missing `orderId` on the return route

If your `NetopiaReturnReceived` listener gets called with an empty `$event->orderId`, the package emits:

```
[warning] Netopia return received with no orderId
```

…with the query string, input, and headers attached. This usually means Netopia is not appending `orderId` to your `NETOPIA_REDIRECT_URL` — double-check the URL you configured on the Netopia merchant dashboard.

### Verify your install end-to-end

A quick sandbox smoke test:

1. **Enable debug logging** — `LOG_LEVEL=debug` in `.env`, then `php artisan config:clear`.
2. **Register a one-shot listener** in `routes/web.php` or a tinker session, just to see events fire:
   ```php
   Event::listen(\MarianDumitru\Netopay\Events\NetopiaPaymentApproved::class,
       fn ($e) => logger()->info('approved', $e->status->toArray()));
   ```
3. **Initiate a payment** with a sandbox test card (Netopia provides these in their dashboard) via `Netopay::start($orderData)`, redirect, complete the form.
4. **Inspect** the `storage/logs/laravel.log` file — you should see the `start payment` request/response, then the IPN payload, then your "approved" log line.
5. **Re-fetch status manually** via `php artisan tinker`:
   ```php
   \MarianDumitru\Netopay\Facades\Netopay::retrieveStatus('<ntpID>', '<orderId>');
   ```
   using the IDs from step 4 to confirm round-trip works.

If any step is missing, the log usually points at which one.

---

## Testing

### Mocking the HTTP client

Use Laravel's `Http::fake()` to mock Netopia responses in your application tests without hitting the real API:

```php
use Illuminate\Support\Facades\Http;
use MarianDumitru\Netopay\Enums\PaymentStatus;

Http::fake([
    '*/payment/card/start' => Http::response([
        'customerAction' => [],
        'error'          => ['code' => '101', 'message' => 'Redirect user to payment page'],
        'payment'        => [
            'ntpID'      => '1234567',
            'status'     => 1,
            'paymentURL' => 'https://secure-sandbox.netopia-payments.com/ui/card?p=TEST',
        ],
    ], 200),
]);
```

### Mocking the client interface

For unit tests that should not touch HTTP at all, bind a fake implementation to `NetopiaClientInterface` in your test service provider:

```php
use MarianDumitru\Netopay\Contracts\NetopiaClientInterface;

$this->app->bind(NetopiaClientInterface::class, FakeNetopiaClient::class);
```

### Asserting events

```php
use Illuminate\Support\Facades\Event;
use MarianDumitru\Netopay\Events\NetopiaPaymentApproved;

Event::fake();

// ... trigger the IPN endpoint

Event::assertDispatched(NetopiaPaymentApproved::class, function ($event) {
    return $event->status->orderId === 'your-order-id';
});
```

> When asserting no package events were dispatched, prefer `Event::assertNotDispatched(SpecificEvent::class)` over `Event::assertNothingDispatched()`. The latter also catches internal Laravel framework events and will produce false failures.

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change. Make sure tests pass before submitting:

```bash
composer test
composer analyse
composer format
```

## Security

If you discover a security vulnerability, please report it via the [GitHub Security Advisory](https://github.com/mariandumitru/netopay/security/advisories/new) rather than the public issue tracker.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for details.
