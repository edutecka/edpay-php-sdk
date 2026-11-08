<?php

namespace EDPay;

class EDPayClient
{
    private string $publicKey;
    private string $privateKey;
    private string $baseUrl;

    public function __construct(string $publicKey, string $privateKey, string $baseUrl = 'https://api-pay.edutecka.com')
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
