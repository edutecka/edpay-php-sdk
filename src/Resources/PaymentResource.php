<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class PaymentResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a payment intent in EDPay.
     */
    public function create(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payments', $params, $idempotencyKey);
    }

    /**
     * Retrieve payment details by ID.
     */
    public function get(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId));
    }

    /**
     * List payments with optional filters.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/payments' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Retrieve a payment's lifecycle event history.
     */
    public function events(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId) . '/events');
    }

    /** Retrieve sanitized dispatch attempt summaries for a payment. */
    public function attempts(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId) . '/attempts');
    }

    /** Legacy alias for creating a payment intent through /v1/payment-checkout. */
    public function createCheckout(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payment-checkout', $params, $idempotencyKey);
    }

    /** Start a child method attempt on the same payment after the latest attempt definitively failed. */
    public function createAttempt(string $paymentId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payments/' . rawurlencode($paymentId) . '/attempts', $params, $idempotencyKey);
    }
}
