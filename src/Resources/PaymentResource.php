<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;
use EDPay\LocalPaymentStore;

class PaymentResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client) { $this->client = $client; }

    /** Create only the payment parent. Select a checkout mode with one of the child methods. */
    public function create(array $params, string $idempotencyKey): array
    {
        $payment = $this->client->request('POST', '/v1/payments', $params, $idempotencyKey);
        $this->client->saveLocalPayment($payment);
        return $payment;
    }

    /** Start a server-to-server payment method for an existing payment. */
    public function privateCheckout(string $paymentId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payments/' . rawurlencode($paymentId) . '/private-checkout', $params, $idempotencyKey);
    }

    public function publicCheckout(string $paymentId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payments/' . rawurlencode($paymentId) . '/public-checkout', $params, $idempotencyKey);
    }

    public function linkCheckout(string $paymentId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payments/' . rawurlencode($paymentId) . '/link-checkout', $params, $idempotencyKey);
    }

    /** Lists executions with their checkout mode and sessions with no submission. */
    public function listCheckouts(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId) . '/checkouts');
    }

    public function getCheckout(string $token): array
    {
        return $this->client->request('GET', '/v1/payments/checkouts/' . rawurlencode($token), [], null, false);
    }

    public function submitCheckoutPayment(string $token, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payments/checkouts/' . rawurlencode($token) . '/payment', $params, $idempotencyKey, false);
    }

    public function retrieveCheckoutPayment(string $token): array
    {
        return $this->client->request('GET', '/v1/payments/checkouts/' . rawurlencode($token) . '/payment', [], null, false);
    }

    public function framePolicy(string $token): array
    {
        return $this->client->request('GET', '/v1/payments/checkouts/' . rawurlencode($token) . '/frame-policy', [], null, false);
    }

    /** Find a locally persisted payment by merchant reference. This method never makes an HTTP request. */
    public function get(string $reference): array
    {
        $payment = $this->client->findLocalPaymentByReference($reference);
        if ($payment === null) {
            throw new \RuntimeException('Payment reference was not found in the local EDPay store.');
        }
        return $payment;
    }

    /** Retrieve the current payment state from EDPay by payment ID. */
    public function retrieve(string $paymentId): array
    {
        $payment = $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId));
        $this->client->saveLocalPayment($payment);
        return $payment;
    }

    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        return $this->client->request('GET', '/v1/payments' . ($queryString ? '?' . $queryString : ''));
    }

    public function events(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId) . '/events');
    }
}
