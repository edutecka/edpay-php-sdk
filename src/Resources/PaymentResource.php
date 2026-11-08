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
     * Cria uma intenção de pagamento no EDPay.
     */
    public function create(array $params, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/payments', $params, $idempotencyKey);
    }

    /**
     * Consulta os detalhes de um pagamento por identificador.
     */
    public function get(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId));
    }

    /**
     * Lista pagamentos com filtros opcionais.
     */
    public function list(array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/payments' . ($queryString ? '?' . $queryString : '');
        return $this->client->request('GET', $path);
    }

    /**
     * Obtém o histórico de eventos de ciclo de vida do pagamento.
     */
    public function events(string $paymentId): array
    {
        return $this->client->request('GET', '/v1/payments/' . rawurlencode($paymentId) . '/events');
    }
}
