<?php

namespace EDPay\Resources;

use EDPay\EDPayClient;

class PublicResource
{
    protected EDPayClient $client;

    public function __construct(EDPayClient $client) { $this->client = $client; }

    public function listPaymentMethods(): array
    {
        return $this->client->request('GET', '/v1/public/payment-methods', [], null, false);
    }
}
