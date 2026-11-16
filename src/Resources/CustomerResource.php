<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class CustomerResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    public function create(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/customers', $params, $idempotencyKey);
    }

    public function get(string $customerId): array
    {
        return $this->client->request('GET', '/v1/customers/' . rawurlencode($customerId));
    }

    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/customers' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /** Update supplied customer fields. An idempotency key is required by the API. */
    public function update(string $customerId, array $params, string $idempotencyKey): array
    {
        return $this->client->request(
            'PATCH',
            '/v1/customers/' . rawurlencode($customerId),
            $params,
            $idempotencyKey
        );
    }
}
