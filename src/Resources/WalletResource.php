<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class WalletResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client) { $this->client = $client; }

    public function list(): array { return $this->client->request('GET', '/v1/wallets'); }

    public function get(string $walletId): array
    {
        return $this->client->request('GET', '/v1/wallets/' . rawurlencode($walletId));
    }

    public function balance(string $walletId): array
    {
        return $this->client->request('GET', '/v1/wallets/' . rawurlencode($walletId) . '/balance');
    }

    public function entries(string $walletId, array $query = []): array
    {
        $queryString = http_build_query($query);
        $path = '/v1/wallets/' . rawurlencode($walletId) . '/entries';
        return $this->client->request('GET', $path . ($queryString ? '?' . $queryString : ''));
    }
}
