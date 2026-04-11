<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    | When true, all requests are sent to Netopia's sandbox environment.
    | Set to false in production.
    */
    'sandbox' => env('NETOPIA_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | Live Credentials
    |--------------------------------------------------------------------------
    */
    'live' => [
        'api_key'       => env('NETOPIA_API_KEY_LIVE'),
        'pos_signature' => env('NETOPIA_POS_SIGNATURE_LIVE', env('NETOPIA_SALES_POINT_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sandbox Credentials
    |--------------------------------------------------------------------------
    */
    'sandbox_credentials' => [
        'api_key'       => env('NETOPIA_API_KEY_SANDBOX'),
        'pos_signature' => env('NETOPIA_POS_SIGNATURE_SANDBOX', env('NETOPIA_SALES_POINT_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'live' => [
            'start'       => env('NETOPIA_API_URL_LIVE', 'https://secure.mobilpay.ro/pay/payment/card/start'),
            'status'      => env('NETOPIA_STATUS_URL_LIVE', 'https://secure.mobilpay.ro/pay/operation/status'),
            'verify_auth' => env('NETOPIA_VERIFY_AUTH_URL_LIVE', 'https://secure.mobilpay.ro/pay/payment/card/verify-auth'),
        ],
        'sandbox' => [
            'start'       => env('NETOPIA_API_URL_SANDBOX', 'https://secure.sandbox.netopia-payments.com/payment/card/start'),
            'status'      => env('NETOPIA_STATUS_URL_SANDBOX', 'https://secure.sandbox.netopia-payments.com/operation/status'),
            'verify_auth' => env('NETOPIA_VERIFY_AUTH_URL_SANDBOX', 'https://secure.sandbox.netopia-payments.com/payment/card/verify-auth'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook URLs sent to Netopia during payment initiation
    |--------------------------------------------------------------------------
    | notify_url  — where Netopia sends the IPN callback (POST).
    |               Leave null to auto-resolve to the package's /netopia/ipn route.
    | redirect_url — where Netopia redirects the user after payment.
    |               Leave null to auto-resolve to the package's /netopia/return route.
    */
    'notify_url'   => env('NETOPIA_NOTIFY_URL'),
    'redirect_url' => env('NETOPIA_REDIRECT_URL'),

    /*
    |--------------------------------------------------------------------------
    | After-payment redirect
    |--------------------------------------------------------------------------
    | Where the package's return controller redirects the user after processing
    | the return callback. Can be a URL or a named route.
    */
    'after_payment_redirect' => env('NETOPIA_AFTER_PAYMENT_REDIRECT', '/'),

    /*
    |--------------------------------------------------------------------------
    | Payment defaults
    |--------------------------------------------------------------------------
    */
    'currency'       => env('NETOPIA_CURRENCY', 'RON'),
    'language'       => env('NETOPIA_LANGUAGE', 'ro'),
    'email_template' => env('NETOPIA_EMAIL_TEMPLATE', 'confirm'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'netopia',
        'middleware' => [],
    ],
];
