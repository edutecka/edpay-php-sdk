<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;
use EDPay\Exceptions\SignatureVerificationException;

class WebhookResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client)
    {
        $this->client = $client;
    }

    public function listEndpoints(): array
    {
        return $this->client->request('GET', '/v1/webhooks/endpoints');
    }

    public function createEndpoint(array $params, string $idempotencyKey): array
    {
        return $this->client->request('POST', '/v1/webhooks/endpoints', $params, $idempotencyKey);
    }

    public function updateEndpoint(string $endpointId, array $params): array
    {
        return $this->client->request('PATCH', '/v1/webhooks/endpoints/' . rawurlencode($endpointId), $params);
    }

    public function disableEndpoint(string $endpointId): array
    {
        return $this->client->request('POST', '/v1/webhooks/endpoints/' . rawurlencode($endpointId) . '/disable');
    }

    public function deleteEndpoint(string $endpointId): array
    {
        return $this->client->request('DELETE', '/v1/webhooks/endpoints/' . rawurlencode($endpointId));
    }

    public function listDeliveries(array $query = []): array
    {
        $queryString = http_build_query($query);
        return $this->client->request('GET', '/v1/webhooks/deliveries' . ($queryString ? '?' . $queryString : ''));
    }

    public function getDelivery(string $deliveryId): array
    {
        return $this->client->request('GET', '/v1/webhooks/deliveries/' . rawurlencode($deliveryId));
    }

    public function retryDelivery(string $deliveryId): array
    {
        return $this->client->request('POST', '/v1/webhooks/deliveries/' . rawurlencode($deliveryId) . '/retry');
    }

    /**
     * Validate the timestamped Payment-Signature HMAC and return the decoded event.
     *
     * @throws SignatureVerificationException If the signature does not match.
     */
    public function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $toleranceSeconds = 300,
        ?int $now = null
    ): array
    {
        if ($signatureHeader === '' || $secret === '' || $toleranceSeconds < 0) {
            throw new SignatureVerificationException("Signature or secret key is missing.");
        }

        $timestamp = null;
        $signature = null;
        foreach (explode(',', $signatureHeader) as $field) {
            $parts = explode('=', trim($field), 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$key, $value] = $parts;
            if ($key === 't') {
                if ($timestamp !== null) {
                    throw new SignatureVerificationException("Webhook signature header is invalid.");
                }
                $timestamp = trim($value);
            } elseif ($key === 'v1') {
                if ($signature !== null) {
                    throw new SignatureVerificationException("Webhook signature header is invalid.");
                }
                $signature = trim($value);
            }
        }

        if (!is_string($timestamp) || !preg_match('/^\d+$/', $timestamp)
            || !is_string($signature) || !preg_match('/^[a-f0-9]{64}$/i', $signature)) {
            throw new SignatureVerificationException("Webhook signature header is invalid.");
        }
        $signedAt = (int) $timestamp;
        if (abs(($now ?? time()) - $signedAt) > $toleranceSeconds) {
            throw new SignatureVerificationException("Webhook signature timestamp is outside the allowed window.");
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        if (!hash_equals(strtolower($expectedSignature), strtolower($signature))) {
            throw new SignatureVerificationException("Webhook signature is invalid.");
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || (!isset($event['type']) && !isset($event['event']))) {
            throw new SignatureVerificationException("Webhook JSON payload is invalid.");
        }

        return $event;
    }
}
