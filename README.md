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

## Continue the same payment with another method

The payment ID and reference identify one payment. Present active gateways as choices; each selected method is a child attempt, with only one attempt in progress at a time. Do not create another payment when the buyer changes method. Create the next attempt only after the current one is definitively failed. If it is still processing or its result is unknown, retrieve it later and wait for reconciliation to avoid charging twice. Use a new idempotency key for each child attempt.

```php
$paymentId = $payment['data']['id']; // save this ID with your order
$current = $client->payments()->get($paymentId);

if ($current['data']['status'] === 'failed') {
    $client->payments()->createAttempt($paymentId, [
        'method' => $selectedMethod, // active code from GET /v1/public/payment-methods
        'phone' => $normalizedPhone,
    ], 'ORDER-10492-ATTEMPT-2');
}
```

For a payment started through a hosted session or payment link, use `checkout()->createSessionPaymentAttempt()` or `checkout()->createPaymentLinkPaymentAttempt()` with that same payment ID and its session token/link ID. The current public checkout form accepts phone-based methods; use the partner payment resource for tokenized instruments.

## Function reference

Each row tells you what the call does and when to use it. Partner calls use the API key pair; public catalog and buyer checkout calls do not. Supply a stable `Idempotency-Key` for writes where required.

| Resource | Function | Endpoint | What it does / when to use it |
|---|---|---|---|
| Payments | `payments()->create` | `POST /v1/payments` | Creates the parent payment and its first method attempt. Start a server-to-server payment; keep its ID and reference for later method attempts and status checks. |
| Payments | `payments()->createCheckout` | `POST /v1/payment-checkout` | Legacy alias for creating a payment; it does not create a hosted checkout session. Use only when migrating an integration that already calls this route. New code should use create. |
| Payments | `checkout()->charge` | `POST /v1/payment-checkout` | Legacy checkout-resource alias for creating a payment; it does not create a hosted session. Keep only while migrating older PHP integrations; use payments()->create for new code. |
| Payments | `payments()->list` | `GET /v1/payments` | Lists payments visible to the current API credentials and environment. Reconcile or show a payment history. Use the limit argument where available. |
| Payments | `payments()->get` | `GET /v1/payments/{id}` | Returns one payment and its current status. Check the result on your server after a redirect or webhook. |
| Payments | `payments()->events` | `GET /v1/payments/{id}/events` | Returns the recorded status changes for a payment. Inspect the payment lifecycle or troubleshoot a status change. |
| Payments | `payments()->attempts` | `GET /v1/payments/{id}/attempts` | Lists the payment's provider attempts; this method is read-only. See which method was tried and the outcome of each attempt. |
| Payments | `payments()->createAttempt` | `POST /v1/payments/{id}/attempts` | Adds another dynamic method as a child attempt. The payment ID, amount and reference stay the same; this attempt uses its own Idempotency-Key. Use after the latest attempt definitively failed. If it is processing or unknown, retrieve the payment and wait for reconciliation to avoid a duplicate charge. A payment supports up to 20 attempts. |
| Hosted checkout | `checkout()->createSession` | `POST /v1/checkout/sessions` | Creates a short-lived checkout session from a payment link. Returns a token for the browser. Send a buyer to EDPay-hosted checkout or embed it on the exact HTTPS origin. |
| Hosted checkout | `checkout()->getSession` | `GET /v1/checkout/sessions/{token}` | Reads public session and checkout details. Pass the token returned by createSession. Load the hosted checkout page using its browser token. |
| Hosted checkout | `checkout()->getPaymentLink` | `GET /v1/checkout/payment-links/{id}` | Reads the public details for an active payment link. Render the buyer-facing link checkout before a payment is submitted. |
| Hosted checkout | `checkout()->framePolicy` | `GET /v1/checkout/sessions/{token}/frame-policy` | Reads whether the session can be embedded and its frame policy. Check embed eligibility before placing checkout in an iframe. |
| Hosted checkout | `checkout()->createSessionPayment` | `POST /v1/checkout/sessions/{token}/payments` | Submits a selected phone-based method and buyer data to a public session; tokenized methods are not accepted by the current hosted form. Requires an idempotency key. Start payment from a hosted checkout form. The response means accepted for processing, not paid. |
| Hosted checkout | `checkout()->createSessionPaymentAttempt` | `POST /v1/checkout/sessions/{token}/payments/{payment_id}/attempts` | Adds another phone-based method to the payment already created from this session. It keeps the same payment ID and reference; hosted checkout currently does not accept tokenized instruments. Use the same session token and payment ID after the previous attempt definitively failed. Wait when the result is processing or unknown. |
| Hosted checkout | `checkout()->getSessionPayment` | `GET /v1/checkout/sessions/{token}/payments/{payment_id}` | Reads the status of a payment created from that session. Show a buyer the latest processing result; verify settlement server-side too. |
| Hosted checkout | `checkout()->createPaymentLinkPayment` | `POST /v1/checkout/payment-links/{id}/payments` | Submits a selected phone-based method and buyer data from a public link; tokenized methods are not accepted by the current hosted form. Requires an idempotency key. Start a buyer payment directly from a payment-link page. |
| Hosted checkout | `checkout()->createPaymentLinkPaymentAttempt` | `POST /v1/checkout/payment-links/{id}/payments/{payment_id}/attempts` | Adds another phone-based method to the payment already created from this link. It keeps the same payment ID and reference; hosted checkout currently does not accept tokenized instruments. Reuse the same link ID and payment ID after the previous attempt definitively failed. Wait when the result is processing or unknown. |
| Hosted checkout | `checkout()->getPaymentLinkPayment` | `GET /v1/checkout/payment-links/{id}/payments/{payment_id}` | Reads the status of a payment submitted through a public link. Refresh the buyer-facing result for a link payment. |
| Payment links | `paymentLinks()->create` | `POST /v1/payment-links` | Creates a fixed-amount public link. Creating the link does not itself create a payment. Collect payment for a fixed amount through a shareable buyer URL. |
| Payment links | `paymentLinks()->list` | `GET /v1/payment-links` | Lists links owned by the authenticated account and environment. Show or reconcile the account's payment links. |
| Payment links | `paymentLinks()->get` | `GET /v1/payment-links/{id}` | Reads one link and its status. Check expiry, amount, or whether the link is still active. |
| Payment links | `paymentLinks()->deactivate` | `POST /v1/payment-links/{id}/deactivate` | Stops an active link from starting future checkout payments. Close a link that should no longer accept buyer payments. |
| Payment links | `—` | `POST /v1/checkout/sessions` | Convenience wrapper that creates a hosted session for the supplied link. Use it instead of calling checkout.createSession when your SDK provides this wrapper. |
| Customers | `customers()->create` | `POST /v1/customers` | Creates a customer record for the authenticated account. Save customer details before associating them with payments or invoices. |
| Customers | `customers()->list` | `GET /v1/customers` | Lists customers in the current account and environment. Build a customer picker or reconcile customer records. |
| Customers | `customers()->get` | `GET /v1/customers/{id}` | Reads one customer record. Load a customer's saved details by ID. |
| Customers | `customers()->update` | `PATCH /v1/customers/{id}` | Updates the supplied customer fields. Correct or refresh a customer's details; use a stable idempotency key where required. |
| Invoices | `invoices()->create` | `POST /v1/invoices` | Creates a draft invoice with its customer and line items. Prepare an invoice before issuing it to the customer. |
| Invoices | `invoices()->list` | `GET /v1/invoices` | Lists invoices, optionally limited to a page size. Show or reconcile invoices for the account. |
| Invoices | `invoices()->get` | `GET /v1/invoices/{id}` | Reads one invoice and its current state. Load invoice details or check its lifecycle. |
| Invoices | `invoices()->finalize` | `POST /v1/invoices/{id}/finalize` | Issues the draft invoice and makes it available for payment. The invoice is complete and ready to send to the customer. |
| Invoices | `invoices()->void` | `POST /v1/invoices/{id}/void` | Voids an invoice that should no longer be payable. Cancel an issued invoice according to its allowed state transitions. |
| Invoice series | `subscriptions()->create` | `POST /v1/invoice-series` | Creates a schedule that issues invoices on the configured interval. Bill a customer repeatedly using a defined start date, interval, and due period. |
| Invoice series | `subscriptions()->list` | `GET /v1/invoice-series` | Lists invoice series; there is no series-detail endpoint in this API version. Review recurring schedules for the account. |
| Invoice series | `subscriptions()->pause` | `POST /v1/invoice-series/{id}/pause` | Pauses future invoice generation for a series. Temporarily stop recurring billing without deleting the schedule. |
| Invoice series | `subscriptions()->resume` | `POST /v1/invoice-series/{id}/resume` | Resumes invoice generation for a paused series. Restart recurring billing after the pause condition has ended. |
| FX quotes | `fx()->quote` | `GET /v1/fx/quote` | Returns an indicative conversion quote for an amount and currency pair. Display a conversion estimate before creating a payment. A quote is not a settlement guarantee. |
| Wallets | `wallets()->list` | `GET /v1/wallets` | Lists wallets available to the authenticated account and environment. Choose a wallet before reading its balance or entries. |
| Wallets | `wallets()->get` | `GET /v1/wallets/{id}` | Reads one wallet's details. Load the wallet metadata for an account view. |
| Wallets | `wallets()->balance` | `GET /v1/wallets/{id}/balance` | Reads the wallet's current balance. Show the available or pending balance for a wallet. |
| Wallets | `wallets()->entries` | `GET /v1/wallets/{id}/entries` | Lists ledger entries with cursor pagination. Show a transaction statement; pass the returned cursor for the next page. |
| Webhooks | `webhooks()->listEndpoints` | `GET /v1/webhook-endpoints` | Lists configured webhook destinations. Review which event destinations are active. |
| Webhooks | `webhooks()->createEndpoint` | `POST /v1/webhook-endpoints` | Registers an HTTPS receiver and selected event types. The signing secret is shown only at creation. Connect your server to receive payment lifecycle events; store the returned secret securely. |
| Webhooks | `webhooks()->updateEndpoint` | `PATCH /v1/webhook-endpoints/{id}` | Updates a destination's URL or subscribed event types. Change a receiver without creating a second endpoint. |
| Webhooks | `webhooks()->disableEndpoint` | `POST /v1/webhook-endpoints/{id}/disable` | Stops future deliveries to an endpoint while retaining its record. Temporarily stop a destination while investigating or migrating it. |
| Webhooks | `webhooks()->deleteEndpoint` | `DELETE /v1/webhook-endpoints/{id}` | Removes a webhook endpoint. Permanently remove a destination that is no longer used. |
| Webhooks | `webhooks()->listDeliveries` | `GET /v1/webhook-deliveries` | Lists delivery attempts and their summary states. Monitor failed, pending, or completed event deliveries. |
| Webhooks | `webhooks()->getDelivery` | `GET /v1/webhook-deliveries/{id}` | Reads one delivery's status and attempt metadata. Inspect one delivery before deciding whether to retry it. |
| Webhooks | `webhooks()->retryDelivery` | `POST /v1/webhook-deliveries/{id}/retry` | Queues a retry for an eligible failed delivery. Retry after fixing the receiver; the endpoint must be active. |
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
| Client utilities | `$client->getEnvironment()` | `Local helper` | Returns the client environment name. Show or log which API-key environment this client is configured to use. |
| Client utilities | `$client->isProduction()` | `Local helper` | Returns whether the configured environment is production. Apply environment-specific display or operational rules. |

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
