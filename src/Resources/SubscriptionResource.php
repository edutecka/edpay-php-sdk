<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class SubscriptionResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a recurring series / subscription.
     */
    public function create(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/invoice-series', $params, $idempotencyKey);
    }

    /**
     * List recurring subscription series.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/invoice-series' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Temporarily pause the issuance of subscription cycles.
     */
    public function pause(string $seriesId): array
    {
        return $this->client->request('POST', '/v1/invoice-series/' . rawurlencode($seriesId) . '/pause');
    }

    /**
     * Resume a paused subscription.
     */
    public function resume(string $seriesId): array
    {
        return $this->client->request('POST', '/v1/invoice-series/' . rawurlencode($seriesId) . '/resume');
    }
}
