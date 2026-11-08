<?php

namespace EDPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \EDPay\Resources\PaymentResource payments()
 * @method static \EDPay\Resources\CheckoutResource checkout()
 * @method static \EDPay\Resources\PaymentLinkResource paymentLinks()
 * @method static \EDPay\Resources\CustomerResource customers()
 * @method static \EDPay\Resources\WebhookResource webhooks()
 * @method static array request(string $method, string $path, array $data = [], ?string $idempotencyKey = null)
 *
 * @see \EDPay\EDPayClient
 */
class Edpay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'edpay';
    }
}
