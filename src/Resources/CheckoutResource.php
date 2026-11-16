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
     * Submit a payment through the selected method's checkout flow.
     * The method identifier and any required fields depend on the selected gateway.
     */
    public function charge(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/payment-checkout', $params, $idempotencyKey);
    }

    /**
     * Create a hosted checkout session (Hosted Checkout Session).
     */
    public function createSession(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/checkout/sessions', $params, $idempotencyKey);
    }

    /**
     * Retrieve a checkout session's status by its public token.
     */
    public function getSession(string $token): array
    {
        return $this->client->request('GET', '/v1/checkout/sessions/' . rawurlencode($token), [], null, false);
    }

    public function framePolicy(string $token): array
    {
        return $this->client->request('GET', '/v1/checkout/sessions/' . rawurlencode($token) . '/frame-policy', [], null, false);
    }

    public function createSessionPayment(string $token, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/checkout/sessions/' . rawurlencode($token) . '/payments', $params, $idempotencyKey, false);
    }

    public function getSessionPayment(string $token, string $paymentId): array
    {
        return $this->client->request('GET', '/v1/checkout/sessions/' . rawurlencode($token) . '/payments/' . rawurlencode($paymentId), [], null, false);
    }

    /** Switch method for the same session payment after its latest attempt definitively failed. */
    public function createSessionPaymentAttempt(string $token, string $paymentId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/checkout/sessions/' . rawurlencode($token) . '/payments/' . rawurlencode($paymentId) . '/attempts', $params, $idempotencyKey, false);
    }

    public function getPaymentLink(string $linkId): array
    {
        return $this->client->request('GET', '/v1/checkout/payment-links/' . rawurlencode($linkId), [], null, false);
    }

    public function createPaymentLinkPayment(string $linkId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/checkout/payment-links/' . rawurlencode($linkId) . '/payments', $params, $idempotencyKey, false);
    }

    public function getPaymentLinkPayment(string $linkId, string $paymentId): array
    {
        return $this->client->request('GET', '/v1/checkout/payment-links/' . rawurlencode($linkId) . '/payments/' . rawurlencode($paymentId), [], null, false);
    }

    /** Switch method for the same payment-link payment after its latest attempt definitively failed. */
    public function createPaymentLinkPaymentAttempt(string $linkId, string $paymentId, array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/checkout/payment-links/' . rawurlencode($linkId) . '/payments/' . rawurlencode($paymentId) . '/attempts', $params, $idempotencyKey, false);
    }
}
