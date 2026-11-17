<?php

namespace EDPay;

use EDPay\Exceptions\EDPayException;
use EDPay\LocalPaymentStore;
use EDPay\Resources\PaymentResource;
use EDPay\Resources\CustomerResource;
use EDPay\Resources\InvoiceResource;
use EDPay\Resources\SubscriptionResource;
use EDPay\Resources\WebhookResource;
use EDPay\Resources\FxResource;
use EDPay\Resources\WalletResource;
use EDPay\Resources\PublicResource;

class EDPayClient
{
    public const VERSION = '1.5.1';
    public const DEFAULT_BASE_URI = 'https://api-pay.edutecka.com';

    protected string $publicKey;
    protected string $privateKey;
    protected string $environment;
    protected string $baseUri;
    protected int $timeout;

    protected ?PaymentResource $payments = null;
    protected ?CustomerResource $customers = null;
    protected ?InvoiceResource $invoices = null;
    protected ?SubscriptionResource $subscriptions = null;
    protected ?WebhookResource $webhooks = null;
    protected ?FxResource $fx = null;
    protected ?WalletResource $wallets = null;
    protected ?PublicResource $paymentMethods = null;
    protected ?LocalPaymentStore $localPaymentStore = null;

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
        $publicMode = preg_match('/^pk_(test|live)_[a-fA-F0-9]{24}$/D', $this->publicKey, $publicMatch) ? $publicMatch[1] : null;
        $privateMode = preg_match('/^sk_(test|live)_[a-fA-F0-9]{64}$/D', $this->privateKey, $privateMatch) ? $privateMatch[1] : null;
        if ($publicMode === null || $privateMode === null) {
            throw new \InvalidArgumentException('A valid pk_test_/pk_live_ public key (24 hex characters) and sk_test_/sk_live_ private key (64 hex characters) are required.');
        }
        if ($publicMode !== $privateMode) {
            throw new \InvalidArgumentException('EDPay public and private keys must use the same environment (test or live).');
        }
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

    public function setLocalPaymentStore(?LocalPaymentStore $store): void { $this->localPaymentStore = $store; }

    public function saveLocalPayment(array $payment): void { if ($this->localPaymentStore !== null) $this->localPaymentStore->save($payment); }

    public function findLocalPaymentByReference(string $reference): ?array
    {
        if ($this->localPaymentStore === null) {
            throw new \LogicException('Local payment lookup requires the Laravel EDPay database store.');
        }
        return $this->localPaymentStore->findByReference($reference);
    }

    public function customers(): CustomerResource
    {
        if ($this->customers === null) {
            $this->customers = new CustomerResource($this);
        }
        return $this->customers;
    }

    public function invoices(): InvoiceResource
    {
        if ($this->invoices === null) {
            $this->invoices = new InvoiceResource($this);
        }
        return $this->invoices;
    }

    public function subscriptions(): SubscriptionResource
    {
        if ($this->subscriptions === null) {
            $this->subscriptions = new SubscriptionResource($this);
        }
        return $this->subscriptions;
    }

    public function webhooks(): WebhookResource
    {
        if ($this->webhooks === null) {
            $this->webhooks = new WebhookResource($this);
        }
        return $this->webhooks;
    }

    public function fx(): FxResource
    {
        return $this->fx ??= new FxResource($this);
    }

    public function wallets(): WalletResource
    {
        return $this->wallets ??= new WalletResource($this);
    }

    public function paymentMethods(): PublicResource
    {
        return $this->paymentMethods ??= new PublicResource($this);
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
     * Execute an authenticated HTTP request to the EDPay API.
     *
     * @throws EDPayException
     */
    public function request(string $method, string $path, array $data = [], ?string $idempotencyKey = null, bool $authenticated = true): array
    {
        $url = $this->baseUri . '/' . ltrim($path, '/');

        $headers = [
            'Accept: application/json',
            'User-Agent: EDPay-PHP-SDK/' . self::VERSION,
        ];
        if ($authenticated) {
            $headers[] = 'X-EDPay-Public-Key: ' . $this->publicKey;
            $headers[] = 'X-EDPay-Private-Key: ' . $this->privateKey;
        }

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
                "Network communication with the EDPay API failed: {$curlError}",
                $curlErrno,
                'network_error',
                0
            );
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorCode = $decoded['error']['code'] ?? 'api_error';
            $message = $decoded['error']['message'] ?? "HTTP {$httpCode} error returned by the EDPay API.";
            throw new EDPayException($message, $httpCode, $errorCode, $httpCode, $decoded);
        }

        return is_array($decoded) ? $decoded : ['raw' => $response];
    }
}
