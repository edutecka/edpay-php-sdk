# EDPay PHP SDK

The EDPay PHP SDK is a server-side client for the EDPay payment platform. It provides payment, customer, invoice, wallet, foreign-exchange, and webhook operations, plus webhook signature verification.

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

Every partner API request requires both `EDPAY_PUBLIC_KEY` (`pk_test_` or `pk_live_`) and `EDPAY_PRIVATE_KEY` (`sk_test_` or `sk_live_`) from the same environment. The SDK rejects missing, blank, or mismatched key pairs when creating the client. Public catalog requests and buyer-facing checkout requests authenticated by a checkout token do not use the partner key pair; never put the private key in browser code. The Laravel service provider also refuses to resolve the client if either key is unset or blank.

Keep both API keys on your server. Use test keys for sandbox requests. The SDK sends requests to `https://api-pay.edutecka.com` by default; only override the base URL when EDPay provides a different host for your account.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use EDPay\EDPayClient;

$client = new EDPayClient(
    getenv('EDPAY_PUBLIC_KEY'),
    getenv('EDPAY_PRIVATE_KEY'),
    getenv('EDPAY_API_URL') ?: EDPayClient::DEFAULT_BASE_URI
);

$payment = $client->payments()->create([
    'amount_minor' => 50000, // 500.00 MZN
    'currency' => 'MZN',
    'reference' => 'ORDER-10492',
    'description' => 'Example order',
], 'ORDER-10492-CREATE-1'); // required idempotency key for payment creation

var_export($payment);
```

Partner API requests send `X-EDPay-Public-Key` and `X-EDPay-Private-Key` headers. Public catalog and token-based checkout requests omit them. The SDK adds `Idempotency-Key` for writes that require one. Do not expose the private key in browser or mobile-app code. Amounts are in minor units; for MZN, `50000` represents MZN 500.00. `payments()->create()` creates only the parent. Choose a dynamic method later with `privateCheckout()`, or let the buyer choose through hosted or link checkout. Method-specific fields such as `phone` and `channel` belong to that child operation.

Populate a payment-method selector from the public catalog and use active catalog codes to populate the buyer's available choices; the SDK does not hard-code gateway identifiers:

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
EDPAY_WEBHOOK_SECRET=whsec_...
```

Configure the API keys for the environment you want to use. The API base URL defaults to `https://api-pay.edutecka.com`. A Laravel service can use the facade as follows:

Laravel uses the `Edpay` facade and publishes migrations with the `edpay_` table prefix. `Edpay::payments()->create(...)` creates a parent payment; use the matching child checkout method after saving the returned ID and reference. `Edpay::payments()->get($reference)` searches the local store without an HTTP request. `retrieve($paymentId)` performs the remote status lookup.
## Choose a checkout mode for one payment

`payments()->create()` creates the parent record only. Save its ID and reference, then choose a child checkout operation. The same payment can use private API processing, hosted checkout, or a shareable link checkout; its ID, amount, and reference stay the same.

```php
$payment = $client->payments()->create([
    'amount_minor' => 50000,
    'currency' => 'MZN',
    'reference' => 'ORDER-10492',
], 'ORDER-10492-CREATE');
$paymentId = $payment['data']['id'];
$checkout = $client->payments()->publicCheckout(
    $paymentId, [], 'ORDER-10492-PUBLIC-CHECKOUT');
// Or call privateCheckout($paymentId, $params, $key)
// or linkCheckout($paymentId, $params, $key).
```

Use `listCheckouts($paymentId)` to read private executions, public/link executions, and sessions where no method was submitted; each execution keeps its originating checkout mode. Use `retrieve($paymentId)` to read current remote payment status. In Laravel, `payments()->get($reference)` looks up an already stored payment locally without HTTP; use it to reuse the existing record and avoid creating a duplicate.

### M-Pesa and e-Mola PIN confirmation

For `method: "mpesa"` or `"emola"`, the provider's 60-second PIN window starts only after the Master delivers the charge. The API's 80-second `private-checkout` deadline starts with the API request, so validation, queueing, and dispatch latency already consume it; it does not wait another 80 seconds after the provider window starts. It returns HTTP `200` with a terminal attempt or `202` if the result is still pending, processing, or unknown when the deadline expires. The SDK transport timeout defaults to 90 seconds; keep a custom timeout above 80 seconds. If the caller disconnects, durable processing continues; configured webhook endpoints receive the event when the payment result is confirmed. You can also query `GET /v1/payments/{id}` and `GET /v1/payments/{id}/checkouts`. Do not resubmit while the attempt is pending or unknown.

## Function reference

Each row tells you what the call does and when to use it. Partner calls use the API key pair; public catalog and buyer checkout calls do not. Supply a stable `Idempotency-Key` for writes where required.

| Resource | Function | Endpoint | What it does / when to use it |
|---|---|---|---|
| Payments · local Laravel lookup | `payments()->get(reference)` | No HTTP request | Finds an already persisted payment by merchant reference; use it to reuse the local record instead of creating a duplicate. |
| Payments | `payments()->create` | `POST /v1/payments` | Creates the parent payment only. Save its ID and reference, then choose a checkout mode. |
| Payments | `payments()->privateCheckout` | `POST /v1/payments/{id}/private-checkout` | Submits a selected method from your server for the existing payment. Use when your backend controls the interface. |
| Payments | `payments()->publicCheckout` | `POST /v1/payments/{id}/public-checkout` | Creates an opaque token for EDPay-hosted checkout on the existing payment. |
| Payments | `payments()->linkCheckout` | `POST /v1/payments/{id}/link-checkout` | Creates a shareable buyer token for the existing payment; it does not create another payment. |
| Payments | `payments()->listCheckouts` | `GET /v1/payments/{id}/checkouts` | Lists executions with their checkout mode and sessions where no method was submitted. |
| Payments | `payments()->retrieve` | `GET /v1/payments/{id}` | Retrieves remote payment status. |
| Payments | `payments()->events` | `GET /v1/payments/{id}/events` | Reads recorded payment status changes. |
| Payments | `payments()->list` | `GET /v1/payments` | Lists account payments for reconciliation or history. |
| Hosted checkout | `payments()->getCheckout` | `GET /v1/payments/checkouts/{token}` | Loads the buyer-facing hosted or link checkout with its opaque token. |
| Hosted checkout | `payments()->submitCheckoutPayment` | `POST /v1/payments/checkouts/{token}/payment` | Submits the buyer-selected method and required method fields with an idempotency key. |
| Hosted checkout | `payments()->retrieveCheckoutPayment` | `GET /v1/payments/checkouts/{token}/payment` | Reads the parent payment status without exposing its ID to the buyer. |
| Hosted checkout | `payments()->framePolicy` | `GET /v1/payments/checkouts/{token}/frame-policy` | Checks whether the hosted checkout can be embedded. |
| Customers | `customers()->create` | `POST /v1/customers` | Creates a customer record for the authenticated account. Save customer details before associating them with payments or invoices. |
| Customers | `customers()->list` | `GET /v1/customers` | Lists customers in the current account and environment. Build a customer picker or reconcile customer records. |
| Customers | `customers()->get` | `GET /v1/customers/{id}` | Reads one customer record. Load a customer's saved details by ID. |
| Customers | `customers()->update` | `PATCH /v1/customers/{id}` | Updates the supplied customer fields. Correct or refresh a customer's details; use a stable idempotency key where required. |
| Invoices | `invoices()->create` | `POST /v1/invoices` | Creates a draft invoice with its customer and line items. Prepare an invoice before issuing it to the customer. |
| Invoices | `invoices()->list` | `GET /v1/invoices` | Lists invoices, optionally limited to a page size. Show or reconcile invoices for the account. |
| Invoices | `invoices()->get` | `GET /v1/invoices/{id}` | Reads one invoice and its current state. Load invoice details or check its lifecycle. |
| Invoices | `invoices()->finalize` | `POST /v1/invoices/{id}/finalize` | Issues the draft invoice and makes it available for payment. The invoice is complete and ready to send to the customer. |
| Invoices | `invoices()->void` | `POST /v1/invoices/{id}/void` | Voids an invoice that should no longer be payable. Cancel an issued invoice according to its allowed state transitions. |
| Invoice series | `subscriptions()->create` | `POST /v1/invoices/series` | Creates a schedule that issues invoices on the configured interval. Bill a customer repeatedly using a defined start date, interval, and due period. |
| Invoice series | `subscriptions()->list` | `GET /v1/invoices/series` | Lists invoice series; there is no series-detail endpoint in this API version. Review recurring schedules for the account. |
| Invoice series | `subscriptions()->pause` | `POST /v1/invoices/series/{id}/pause` | Pauses future invoice generation for a series. Temporarily stop recurring billing without deleting the schedule. |
| Invoice series | `subscriptions()->resume` | `POST /v1/invoices/series/{id}/resume` | Resumes invoice generation for a paused series. Restart recurring billing after the pause condition has ended. |
| FX quotes | `fx()->quote` | `GET /v1/fx/quote` | Returns an indicative conversion quote for an amount and currency pair. Display a conversion estimate before creating a payment. A quote is not a settlement guarantee. |
| Wallets | `wallets()->list` | `GET /v1/wallets` | Lists wallets available to the authenticated account and environment. Choose a wallet before reading its balance or entries. |
| Wallets | `wallets()->get` | `GET /v1/wallets/{id}` | Reads one wallet's details. Load the wallet metadata for an account view. |
| Wallets | `wallets()->balance` | `GET /v1/wallets/{id}/balance` | Reads the wallet's current balance. Show the available or pending balance for a wallet. |
| Wallets | `wallets()->entries` | `GET /v1/wallets/{id}/entries` | Lists ledger entries with cursor pagination. Show a transaction statement; pass the returned cursor for the next page. |
| Webhooks | `webhooks()->listEndpoints` | `GET /v1/webhooks/endpoints` | Lists configured webhook destinations. Review which event destinations are active. |
| Webhooks | `webhooks()->createEndpoint` | `POST /v1/webhooks/endpoints` | Registers an HTTPS receiver and selected event types. The signing secret is shown only at creation. Connect your server to receive payment lifecycle events; store the returned secret securely. |
| Webhooks | `webhooks()->updateEndpoint` | `PATCH /v1/webhooks/endpoints/{id}` | Updates a destination's URL or subscribed event types. Change a receiver without creating a second endpoint. |
| Webhooks | `webhooks()->disableEndpoint` | `POST /v1/webhooks/endpoints/{id}/disable` | Stops future deliveries to an endpoint while retaining its record. Temporarily stop a destination while investigating or migrating it. |
| Webhooks | `webhooks()->deleteEndpoint` | `DELETE /v1/webhooks/endpoints/{id}` | Removes a webhook endpoint. Permanently remove a destination that is no longer used. |
| Webhooks | `webhooks()->listDeliveries` | `GET /v1/webhooks/deliveries` | Lists delivery attempts and their summary states. Monitor failed, pending, or completed event deliveries. |
| Webhooks | `webhooks()->getDelivery` | `GET /v1/webhooks/deliveries/{id}` | Reads one delivery's status and attempt metadata. Inspect one delivery before deciding whether to retry it. |
| Webhooks | `webhooks()->retryDelivery` | `POST /v1/webhooks/deliveries/{id}/retry` | Queues a retry for an eligible failed delivery. Retry after fixing the receiver; the endpoint must be active. |
| Public catalog and optional support chat | `paymentMethods()->listPaymentMethods` | `GET /v1/public/payment-methods` | Returns active payment methods and their public requirements without partner keys. Build a method selector and validate the selected method's required fields. |
| Contact helpers | `ContactFormatter::normalize` | `Local helper` | Removes display punctuation and returns the phone value in the configured national or international form. Prepare a phone value before validating or sending it to a method that requires one. |
| Contact helpers | `ContactFormatter::format` | `Local helper` | Formats a valid phone number for display; it does not change the API payload automatically. Show a readable phone number in a form or confirmation screen. |
| Contact helpers | `ContactFormatter::addCountryPrefix` | `Local helper` | Adds the configured country calling code to a national number. An integration needs the international form, such as 258 plus national digits. |
| Contact helpers | `ContactFormatter::removeCountryPrefix` | `Local helper` | Removes the configured country calling code and returns national digits. A selected method expects the national number without a country code. |
| Contact helpers | `ContactFormatter::isValid` | `Local helper` | Checks length, allowed characters, country code, and configured prefixes; returns true or false. Reject invalid input before calling the API. It does not replace server-side validation. |
| Contact helpers | `ContactFormatter::optionsForMethod` | `Local helper` | Returns known contact rules for the selected method, or null when no preset exists. Load rules before validating a phone; configure rules from the live catalog for other phone methods. |
| Webhook signature verification | `webhooks()->constructEvent` | `Local helper` | Checks the Payment-Signature HMAC and timestamp against the unmodified request body. Verify the signature before parsing or acting on an incoming webhook. Use the raw bytes and keep the secret server-side. |
| Client utilities | `$client->request` | `Raw API request` | Sends a request to an API path without a dedicated resource wrapper. The authenticated argument controls whether partner key headers are sent. Use only for a supported endpoint that has no SDK method; keep private keys on the server and apply the endpoint's scope and idempotency rules. |
| Client utilities | `$client->getPublicKey()` | `Local helper` | Returns the configured public key. Read the public key for display or browser configuration; never expose the private key. |
| Client utilities | `$client->getEnvironment()` | `Local helper` | Reports sandbox or production from the matched public-key prefix; it does not change the API host. |
| Client utilities | `$client->isProduction()` | `Local helper` | Returns whether the public key starts with pk_live_. Use it for display or operational rules without choosing a separate endpoint. |

Resource calls return decoded PHP arrays. `EDPayException` includes status, response body, request ID, and retry-after metadata.
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

The verified event envelope is returned as a PHP array. Its shape is `id: string`, `object: "event"`, `type` (one of `payment.created`, `payment.updated`, `payment.succeeded`, `payment.failed`, `payment.cancelled`, or `payment.reconciliation_required`), `created_at: string`, and `data`. Payment data contains `object: "payment"`, string `id`, `reference`, `trans_id`, and `method`; status is one of `pending`, `processing`, `unknown`, `succeeded`, `failed`, or `cancelled`; `amount_minor` is an integer and currency is `MZN`, `USD`, or `EUR`. `platform_fee_minor` and `receiver_net_minor` are optional integers, as are reconciliation fields `reconciliation_status` (`manual_review`) and `reconciliation_code` (string). The verifier returns the decoded array only after signature validation.

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
