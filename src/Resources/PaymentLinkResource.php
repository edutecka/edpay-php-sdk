<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class PaymentLinkResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a reusable payment link with a fixed amount in minor currency units (cents).
     */
    public function create(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payment-links', $params, $idempotencyKey);
    }

    /**
     * Retrieve payment link details.
     */
    public function get(string $linkId): array
    {
        return $this->client->request('GET', '/v1/payment-links/' . rawurlencode($linkId));
    }

    /**
     * List payment links created in the current environment.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/payment-links' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Deactivate an active payment link.
     */
    public function deactivate(string $linkId, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payment-links/' . rawurlencode($linkId) . '/deactivate', [], $idempotencyKey);
    }
}
