# EDPay PHP SDK

The EDPay PHP SDK is a server-side client for the EDPay payment platform. It provides resources for payments, checkout, payment links, customers, invoices, subscriptions, and webhook signature verification.

## Requirements

- PHP 7.4 or later for the core SDK
- PHP `curl` and `json` extensions
- Composer
- Laravel integration: an existing Laravel application (Laravel classes are provided by this repository; Laravel is not a core SDK dependency)

## Install with Composer

From your application's root directory, install the SDK with Composer:

```bash
composer require edutecka/edpay-php-sdk
```

Composer installs the SDK and registers its autoloader. You can then use the `EDPay\EDPayClient` class in your PHP application.

## Create a payment

Keep both API keys on your server. Use sandbox credentials while testing. Set `EDPAY_API_URL` to the API base URL provided for your environment; the constructor's environment argument does not select or change the API host. If `EDPAY_API_URL` is unset, the client uses `https://api-pay.edutecka.com`.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use EDPay\EDPayClient;

$client = new EDPayClient(
    getenv('EDPAY_PUBLIC_KEY'),
    getenv('EDPAY_PRIVATE_KEY'),
    'sandbox',
    getenv('EDPAY_API_URL') ?: EDPayClient::DEFAULT_BASE_URI
);

$payment = $client->payments()->create([
    'amount_minor' => 50000, // 500.00 MZN
    'currency' => 'MZN',
    'method' => getenv('EDPAY_METHOD'), // an identifier from GET /v1/public/payment-methods
    'reference' => 'ORDER-10492',
    'description' => 'Example order',
    'phone' => '+25884xxxxxxx',
], 'ORDER-10492'); // optional idempotency key

var_export($payment);
```

The SDK sends `X-EDPay-Public-Key` and `X-EDPay-Private-Key` headers and adds `Idempotency-Key` when the optional key is supplied. Do not expose the private key in browser or mobile-app code. Amounts are in minor units; for MZN, `50000` represents MZN 500.00. The `method` value is gateway-agnostic and should be an identifier supported by your account.

## Laravel integration

When the SDK is available to a Laravel application's Composer autoloader, Laravel package discovery registers the service provider and `Edpay` facade. Publish the configuration and, if needed, the optional migrations:

```bash
php artisan vendor:publish --tag=edpay-config
php artisan vendor:publish --tag=edpay-migrations
php artisan migrate
```

Set server-side values in the application environment (do not commit `.env`):

```dotenv
EDPAY_PUBLIC_KEY=pk_test_...
EDPAY_PRIVATE_KEY=sk_test_...
EDPAY_ENVIRONMENT=sandbox
EDPAY_API_URL=https://sandbox-api.example
EDPAY_WEBHOOK_SECRET=whsec_...
```

Set `EDPAY_API_URL` to the sandbox API base URL supplied for your account. The example hostname above is a placeholder, not an EDPay endpoint. A Laravel service can use the facade as follows:

```php
use EDPay\Laravel\Facades\Edpay;

$payment = Edpay::checkout()->charge([
    'amount_minor' => 50000,
    'currency' => 'MZN',
    'method' => config('services.edpay.method'),
    'reference' => 'ORDER-10492',
]);
```

`checkout()->charge()` sends a request to `POST /v1/payment-checkout`. For other operations, the client exposes `payments()`, `checkout()`, `paymentLinks()`, `customers()`, `invoices()`, `subscriptions()`, and `webhooks()` resources.

## Verify webhooks

Read the raw request body and the `X-EDPay-Signature` header, then verify the signature with the webhook secret before updating an order or account:

```php
$event = $client->webhooks()->constructEvent(
    $rawRequestBody,
    $signatureHeader,
    getenv('EDPAY_WEBHOOK_SECRET')
);
```

The verifier uses HMAC-SHA256 and throws `EDPay\Exceptions\SignatureVerificationException` if verification fails or the payload is invalid. Register the webhook URL and required events in the EDPay dashboard.

## Documentation

- [SDK integration guide](https://docs-pay.edutecka.com/sdks/)
- [Payments API reference](https://docs-pay.edutecka.com/api/payments/)
- [API authentication and keys](https://docs-pay.edutecka.com/guides/authentication/)
- [Webhook guide](https://docs-pay.edutecka.com/api/webhooks/)

## License

The Composer package metadata declares the MIT license.

## Other official SDK repositories

- [JavaScript / TypeScript](https://github.com/edutecka/edpay-javascript-sdk)
- [Python](https://github.com/edutecka/edpay-python-sdk)
- [Java](https://github.com/edutecka/edpay-java-sdk)
- [.NET](https://github.com/edutecka/edpay-dot-net-sdk)

## Official e-commerce plugin repositories

These repositories are official integration previews. Check each repository's status and test it in a sandbox before using it with a live store.

- [WooCommerce](https://github.com/edutecka/edpay-woocommerce-plugin)
- [Shopify](https://github.com/edutecka/edpay-shopify-app)
- [Magento / Adobe Commerce](https://github.com/edutecka/edpay-magento2-module)
- [PrestaShop](https://github.com/edutecka/edpay-prestashop-module)
- [OpenCart](https://github.com/edutecka/edpay-opencart-extension)
- [WHMCS](https://github.com/edutecka/edpay-whmcs-gateway)
