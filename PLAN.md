# Development Roadmap — EDPay PHP SDK

## 1. Overview and Principles
- **Gateway-Agnostic**: support for multiple gateways (M-Pesa, e-Mola, debit/credit cards, Apple Pay, Google Pay).
- **Idempotency Handling**: automatically send the `Idempotency-Key` header for safe mutations.
- **Webhook Signing and Validation**: cryptographic verification of the `X-EDPay-Signature` header using HMAC-SHA256.
- **Structured Error Handling**: map the API's standard error codes (`400`, `401`, `409`, `422`, `500`).
- **USSD Timeout Handling**: 60-second interactive flow with support for asynchronous polling.

## 2. Implementation Roadmap
- [x] **Phase 0: Repository Initialization & Base Architecture**
  - Create the language's standard directory structure.
  - Configure the dependency manager and packaging.
  - Add initial documentation and a contribution guide.
- [ ] **Phase 1: HTTP Client and Authentication**
  - Configure the base URL, timeouts, and headers (`X-EDPay-Public-Key`, `X-EDPay-Private-Key`).
  - Add automatic idempotency handling.
- [ ] **Phase 2: Core API Resources**
  - `Payments`: create a payment, retrieve its status, and cancel it.
  - `Payments`: create a payment parent and select hosted, private, or link checkout as child operations.
  - Standalone payment-link resources are replaced by `Payments::linkCheckout`.
  - `Invoices`: manage invoices with notes and discounts.
  - `Customers`: manage customers and tax identification.
  - `Wallets`: retrieve balances and statements.
  - `PaymentMethods`: list active public gateways and payment methods.
- [ ] **Phase 3: Webhooks Module**
  - Parse JSON events.
  - Validate signatures using the account secret.
- [ ] **Phase 4: Unit and Integration Tests**
  - Test suite with API mocks.
  - Tests against the Sandbox environment.
- [ ] **Phase 5: Publish to the Official Package Registry**

## 3. Plugins & Extensions (Coming Soon)
- WooCommerce (WordPress) module
- Shopify app
- Magento 2 module
- PrestaShop 8 module
