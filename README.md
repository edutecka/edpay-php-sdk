# EDPay PHP SDK

The EDPay PHP SDK is a server-side client for the EDPay payment platform. It provides resources for payments, checkout, payment links, customers, invoices and invoice series, wallets, foreign-exchange quotes, webhook management, and webhook signature verification.

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

Keep both API keys on your server. Use test keys for sandbox requests. The SDK sends requests to `https://api-pay.edutecka.com` by default; only override the base URL when EDPay provides a different host for your account.

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
], 'ORDER-10492-CREATE-1'); // required idempotency key for payment creation

var_export($payment);
```

Partner API requests send `X-EDPay-Public-Key` and `X-EDPay-Private-Key` headers. Public catalog and token-based checkout requests omit them. The SDK adds `Idempotency-Key` for writes that require one. Do not expose the private key in browser or mobile-app code. Amounts are in minor units; for MZN, `50000` represents MZN 500.00. The `method` value is dynamic; use an identifier returned by `GET /v1/public/payment-methods` or supported by your account. `phone` is required only when the selected method requires a phone number; tokenized methods send their instrument through `channel`.

Populate a payment-method selector from the public catalog and pass the user's selected `code` to `payments()->create()`; the SDK does not hard-code gateway identifiers:

```php
$catalog = $client->paymentMethods()->listPaymentMethods();
$availableMethods = array_filter(
    $catalog['data']['payment_methods'] ?? [],
    static fn (array $method): bool => !empty($method['enabled'])
);
```

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
EDPAY_WEBHOOK_SECRET=whsec_...
```

Configure the API keys for the environment you want to use. The API base URL defaults to `https://api-pay.edutecka.com`. A Laravel service can use the facade as follows:

```php
use EDPay\Laravel\Facades\Edpay;

$payment = Edpay::checkout()->charge([
    'amount_minor' => 50000,
    'currency' => 'MZN',
    'method' => config('services.edpay.method'),
    'phone' => '+25884xxxxxxx',
    'reference' => 'ORDER-10492',
], 'ORDER-10492-CREATE-1');
```

`checkout()->charge()` sends a request to `POST /v1/payment-checkout`. For other operations, the client exposes `payments()`, `checkout()`, `paymentLinks()`, `customers()`, `invoices()`, `subscriptions()`, `webhooks()`, `wallets()`, `fx()`, and `paymentMethods()` resources.

`checkout()->charge()` and `payments()->createCheckout()` are legacy aliases for creating a payment intent; hosted checkout uses payment-link creation followed by `checkout()->createSession()`.

## Available operations

- Payments: `create`, `createCheckout`, `get`, `list`, `events`, `attempts`
- Checkout: `charge`, `createSession`, `getSession`, frame policy, and session or payment-link payment operations
- Payment links: `create`, `get`, `list`, `deactivate`
- Customers: `create`, `get`, `list`, `update`
- Invoices: `create`, `get`, `list`, `finalize`, `void`
- Invoice series (`subscriptions` resource): `create`, `list`, `pause`, `resume`
- Webhooks: endpoint create, list, update, disable, delete, delivery list/detail/retry, and `constructEvent`
- Wallets: list, get, balance, entries; FX quotes; public payment-method catalog.
- Contacts: `EDPay\ContactFormatter::normalize`, `format`, `addCountryPrefix`, `removeCountryPrefix`, and `isValid`.

Resource methods return decoded PHP arrays. Use the returned payment ID to retrieve the payment or its events, and verify a webhook before processing its event.

## Format and validate a phone contact

Contact helpers default to Mozambique's calling code (`258`) and nine national digits. Provide country and selected-method rules when different formatting or prefix requirements apply:

```php
use EDPay\ContactFormatter;

$method = getenv('EDPAY_METHOD') ?: '';
$rules = ContactFormatter::optionsForMethod($method);
$input = getenv('CUSTOMER_PHONE') ?: '';
if ($rules !== null && !ContactFormatter::isValid($input, $rules)) {
    throw new InvalidArgumentException('Phone number is invalid for the selected method.');
}
if ($rules !== null) {
    $phoneForRequest = ContactFormatter::normalize($input, $rules);
    $phoneForDisplay = ContactFormatter::format($input, $rules);
    $nationalDigits = ContactFormatter::removeCountryPrefix($input, $rules);
}
```

`ContactFormatter::optionsForMethod($method)` returns known rules for `emola` (national `86`/`87`, output `national`) and `mpesa` (`84`/`85`, output `country-code`, meaning `258` followed by national digits). It returns `null` for unknown or non-phone methods; use account-provided rules for other phone methods and skip phone validation for tokenized methods. Rules remain configurable.

## Verify webhooks

Read the raw request body and the `Payment-Signature` header, then verify the timestamped signature with the webhook secret before updating an order or account. EDPay signs `timestamp + "." + rawBody`; verification rejects timestamps outside the default five-minute window:

```php
$event = $client->webhooks()->constructEvent(
    $rawRequestBody,
    $signatureHeader,
    getenv('EDPAY_WEBHOOK_SECRET')
);
```

The verifier uses HMAC-SHA256 and throws `EDPay\Exceptions\SignatureVerificationException` if verification fails or the payload is invalid. Register the webhook URL and required events in the EDPay dashboard. The Laravel route registered by the service provider reads `Payment-Signature` and uses the configured `EDPAY_WEBHOOK_SECRET`.

The verified event envelope is returned as a PHP array. Its shape is `id: string`, `object: "event"`, `type` (one of `payment.created`, `payment.updated`, `payment.succeeded`, `payment.failed`, `payment.cancelled`, or `payment.reconciliation_required`), `created_at: string`, and `data`. Payment data contains `object: "payment"`, string `id`, `reference`, nullable `payment_link_id`, `trans_id`, and `method`; status is one of `pending`, `processing`, `unknown`, `succeeded`, `failed`, or `cancelled`; `amount_minor` is an integer and currency is `MZN`, `USD`, or `EUR`. `platform_fee_minor` and `receiver_net_minor` are optional integers, as are reconciliation fields `reconciliation_status` (`manual_review`) and `reconciliation_code` (string). The verifier returns the decoded array only after signature validation.

The public support-chat route streams server-sent events and is not exposed by this release's JSON request helpers.

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
