# Changelog

## 1.5.0

- Add child payment-attempt methods for server payments and hosted checkout sessions/payment links. Attempts reuse the payment ID, amount, and reference.
- Keep method attempts idempotent and document when a failed, processing, or unknown result allows a new attempt.

## 1.4.0

- Add partner resources for payments, customers, payment links, invoices and invoice series, FX, wallets, webhooks, and the public payment-method catalog.
- Add hosted checkout sessions, configurable contact helpers, and method presets for supported phone methods.
- Keep public payment-method catalog and token-based checkout requests unauthenticated so private keys are not sent to public routes; session creation remains authenticated.
- Align payment inputs with the current partner schema and retain `createCheckout` as a legacy alias for the payment-intent endpoint.
