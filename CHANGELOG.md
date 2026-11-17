# Changelog

## 1.6.1

- Remove the configurable SDK environment; matching API key prefixes determine sandbox or production access.
- Keep requests on the single EDPay API host and update Composer metadata to 1.6.1.
- Wait up to 80 seconds from the API request for M-Pesa/e-Mola private checkout. The provider's separate 60-second PIN window starts after Master dispatch, and dispatch latency consumes the API deadline; SDK transport defaults to 90 seconds.

## 1.6.0

- Add the unified payment parent and child checkout operations for private API, hosted checkout, and link checkout.
- Add opaque-token buyer routes and checkout history on the parent payment.
- Keep the local Laravel payment lookup separate from the remote API retrieval.
- Reject Laravel client resolution when either API key is missing or blank.

## 1.5.0

- Improve payment method handling and idempotent payment requests.

## 1.4.0

- Add partner resources for payments, customers, payment links, invoices and invoice series, FX, wallets, webhooks, and the public payment-method catalog.
- Add hosted checkout sessions, configurable contact helpers, and method presets for supported phone methods.
- Keep public payment-method catalog and token-based checkout requests unauthenticated so private keys are not sent to public routes; session creation remains authenticated.
