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
     * Cria uma série recorrente / subscrição periódica.
     */
    public function create(array $params, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/invoice-series', $params, $idempotencyKey);
    }

    /**
     * Obtém os detalhes de uma subscrição por ID.
     */
    public function get(string $seriesId): array
    {
        return $this->client->request('GET', '/v1/invoice-series/' . rawurlencode($seriesId));
    }

    /**
     * Lista séries de subscrição recorrentes.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/invoice-series' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Pausa temporariamente a emissão de ciclos de uma subscrição.
     */
    public function pause(string $seriesId): array
    {
        return $this->client->request('POST', '/v1/invoice-series/' . rawurlencode($seriesId) . '/pause');
    }

    /**
     * Retoma uma subscrição pausada.
     */
    public function resume(string $seriesId): array
    {
        return $this->client->request('POST', '/v1/invoice-series/' . rawurlencode($seriesId) . '/resume');
    }
}
