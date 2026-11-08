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

    public function create(array $params, ?string $idempotencyKey = null): array
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
}
