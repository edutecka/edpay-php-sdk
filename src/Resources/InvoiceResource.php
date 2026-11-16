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
     * Create an invoice with items and a due date.
     */
    public function create(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/invoices', $params, $idempotencyKey);
    }

    /**
     * Retrieve invoice details by ID.
     */
    public function get(string $invoiceId): array
    {
        return $this->client->request('GET', '/v1/invoices/' . rawurlencode($invoiceId));
    }

    /**
     * List invoices with optional filters.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/invoices' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Finalize and issue the invoice, generating a checkout link for settlement.
     */
    public function finalize(string $invoiceId, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/invoices/' . rawurlencode($invoiceId) . '/finalize', [], $idempotencyKey);
    }

    /**
     * Void an issued invoice.
     */
    public function void(string $invoiceId): array
    {
        return $this->client->request('POST', '/v1/invoices/' . rawurlencode($invoiceId) . '/void');
    }
}
