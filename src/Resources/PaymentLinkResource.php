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
     * Cria um link de pagamento reutilizável com montante fixo em minor units (centavos).
     */
    public function create(array $params, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/payment-links', $params, $idempotencyKey);
    }

    /**
     * Obtém detalhes de um link de pagamento.
     */
    public function get(string $linkId): array
    {
        return $this->client->request('GET', '/v1/payment-links/' . rawurlencode($linkId));
    }

    /**
     * Lista links de pagamento criados no ambiente actual.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/payment-links' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Desativa um link de pagamento ativo.
     */
    public function deactivate(string $linkId): array
    {
        return $this->client->request('POST', '/v1/payment-links/' . rawurlencode($linkId) . '/deactivate');
    }
}
