<?php

namespace EDPay;

use EDPay\Exceptions\EDPayException;
use EDPay\Resources\PaymentResource;
use EDPay\Resources\CheckoutResource;
use EDPay\Resources\PaymentLinkResource;
use EDPay\Resources\CustomerResource;
use EDPay\Resources\WebhookResource;

class EDPayClient
{
    public const VERSION = '1.0.0';
    public const DEFAULT_BASE_URI = 'https://api-pay.edutecka.com';

    protected string $publicKey;
    protected string $privateKey;
    protected string $environment;
    protected string $baseUri;
    protected int $timeout;

    protected ?PaymentResource $payments = null;
    protected ?CheckoutResource $checkout = null;
    protected ?PaymentLinkResource $paymentLinks = null;
    protected ?CustomerResource $customers = null;
    protected ?WebhookResource $webhooks = null;

    public function __construct(
        string $publicKey,
        string $privateKey,
        string $environment = 'sandbox',
        ?string $baseUri = null,
        int $timeout = 60
    ) {
        $this->publicKey = trim($publicKey);
        $this->privateKey = trim($privateKey);
        $this->environment = strtolower(trim($environment));
        $this->baseUri = rtrim($baseUri ?? self::DEFAULT_BASE_URI, '/');
        $this->timeout = $timeout;
    }

    public function payments(): PaymentResource
    {
        if ($this->payments === null) {
            $this->payments = new PaymentResource($this);
        }
        return $this->payments;
    }

    public function checkout(): CheckoutResource
    {
        if ($this->checkout === null) {
            $this->checkout = new CheckoutResource($this);
        }
        return $this->checkout;
    }

    public function paymentLinks(): PaymentLinkResource
    {
        if ($this->paymentLinks === null) {
            $this->paymentLinks = new PaymentLinkResource($this);
        }
        return $this->paymentLinks;
    }

    public function customers(): CustomerResource
    {
        if ($this->customers === null) {
            $this->customers = new CustomerResource($this);
        }
        return $this->customers;
    }

    public function webhooks(): WebhookResource
    {
        if ($this->webhooks === null) {
            $this->webhooks = new WebhookResource();
        }
        return $this->webhooks;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Executa requisição HTTP autenticada contra a API da EDPay.
     *
     * @throws EDPayException
     */
    public function request(string $method, string $path, array $data = [], ?string $idempotencyKey = null): array
    {
        $url = $this->baseUri . '/' . ltrim($path, '/');

        $headers = [
            'Accept: application/json',
            'X-EDPay-Public-Key: ' . $this->publicKey,
            'X-EDPay-Private-Key: ' . $this->privateKey,
            'User-Agent: EDPay-PHP-SDK/' . self::VERSION,
        ];

        if ($idempotencyKey !== null) {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $jsonPayload = json_encode($data);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0) {
            throw new EDPayException(
                "Falha na comunicação de rede com a API EDPay: {$curlError}",
                $curlErrno,
                'network_error',
                0
            );
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorCode = $decoded['error']['code'] ?? 'api_error';
            $message = $decoded['error']['message'] ?? "Erro HTTP {$httpCode} retornado pela API EDPay.";
            throw new EDPayException($message, $httpCode, $errorCode, $httpCode, $decoded);
        }

        return is_array($decoded) ? $decoded : ['raw' => $response];
    }
}
