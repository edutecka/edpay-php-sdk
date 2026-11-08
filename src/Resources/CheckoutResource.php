<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class CheckoutResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Executa um checkout direto disparando autorização para o canal selecionado.
     * Suporta canais: mobile_money (e-Mola/M-Pesa), card (Visa/Mastercard),
     * apple_pay, google_pay e bank_transfer.
     */
    public function charge(array $params, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/payment-checkout', $params, $idempotencyKey);
    }

    /**
     * Cria uma sessão de checkout hospedado (Hosted Checkout Session).
     */
    public function createSession(array $params, ?string $idempotencyKey = null): array
    {
        return $this->client->request('POST', '/v1/checkout/sessions', $params, $idempotencyKey);
    }

    /**
     * Consulta estado de uma sessão de checkout por token público.
     */
    public function getSession(string $token): array
    {
        return $this->client->request('GET', '/v1/checkout/sessions/' . rawurlencode($token));
    }
}
