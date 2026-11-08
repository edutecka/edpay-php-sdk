<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class InvoiceResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Cria uma fatura com itens e prazo de vencimento.
     */
    public function create(array $params, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/invoices', $params, $idempotencyKey);
    }

    /**
     * Obtém os detalhes de uma fatura por ID.
     */
    public function get(string $invoiceId): array
    {
        return $this->client->request('GET', '/v1/invoices/' . rawurlencode($invoiceId));
    }

    /**
     * Lista faturas com filtros opcionais.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/invoices' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Finaliza e emite a fatura, gerando o link de checkout para liquidação.
     */
    public function finalize(string $invoiceId, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/invoices/' . rawurlencode($invoiceId) . '/finalize', [], $idempotencyKey);
    }

    /**
     * Anula uma fatura emitida.
     */
    public function void(string $invoiceId): array
    {
        return $this->client->request('POST', '/v1/invoices/' . rawurlencode($invoiceId) . '/void');
    }
}
