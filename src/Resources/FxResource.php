<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class FxResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client) { $this->client = $client; }

    public function quote(string $fromCurrency, string $toCurrency, int $amountMinor): array
    {
        $query = http_build_query([
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'amount_minor' => $amountMinor,
        ]);
        return $this->client->request('GET', '/v1/fx/quote?' . $query);
    }
}
