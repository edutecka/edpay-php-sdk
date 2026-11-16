<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EDPay Platform Credentials (Server-to-Server)
    |--------------------------------------------------------------------------
    |
    | Get your keys in the EDPay Console (https://pay.edutecka.com/dashboard/api-keys).
    | Both keys are required for partner API calls and must belong to the same environment:
    | use pk_test_ with sk_test_ in development, or pk_live_ with sk_live_ in production.
    |
    */
    'public_key' => env('EDPAY_PUBLIC_KEY', ''),
    'private_key' => env('EDPAY_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Operating Environment
    |--------------------------------------------------------------------------
    |
    | Supported values: 'sandbox' (test) or 'production' (live).
    |
    */
    'environment' => env('EDPAY_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | HMAC Webhook Validation Secret
    |--------------------------------------------------------------------------
    |
    | Signing secret used to verify the timestamped Payment-Signature header.
    |
    */
    'webhook_secret' => env('EDPAY_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency used by the SDK configuration. Fees and financial terms
    | are determined by EDPay and may be updated.
    |
    */
    'currency' => env('EDPAY_CURRENCY', 'MZN'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL and Timeout
    |--------------------------------------------------------------------------
    |
    | EDPay services URL and maximum wait time for each cURL request.
    |
    */
    'base_uri' => env('EDPAY_API_URL', 'https://api-pay.edutecka.com'),
    'timeout' => (int) env('EDPAY_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Automatic Laravel Webhook Route
    |--------------------------------------------------------------------------
    |
    | When enabled, the package automatically registers the route for receiving webhooks.
    |
    */
    'webhook' => [
        'enabled' => true,
        'path' => env('EDPAY_WEBHOOK_PATH', '/edpay/webhook'),
    ],
];
