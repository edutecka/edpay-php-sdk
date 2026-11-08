# EDPay PHP & Laravel SDK

SDK oficial da plataforma **EDPay** para integração em aplicações **PHP** e frameworks **Laravel**.

Permite processar pagamentos de forma transparente em múltiplos gateways (Carteiras Móveis **e-Mola** e **M-Pesa**, Cartões de Débito/Crédito **Visa / Mastercard**, **Apple Pay**, **Google Pay** e **Referência Bancária**).

---

## Requisitos

- PHP >= 7.4
- Extensões PHP `curl` e `json`
- Laravel 9.x, 10.x ou 11.x (opcional)

---

## Instalação

```bash
composer require edutecka/edpay-php-sdk
```

---

## Integração com Laravel

### 1. Auto-Discovery & Publicação

O SDK regista automaticamente o `EdpayServiceProvider` e a Facade `Edpay`.

Publique o ficheiro de configuração `config/edpay.php`:

```bash
php artisan vendor:publish --tag=edpay-config
```

Publique e execute as migrações de base de dados para tabelas de pagamentos e auditoria de webhooks:

```bash
php artisan vendor:publish --tag=edpay-migrations
php artisan migrate
```

### 2. Variáveis de Ambiente (`.env`)

```env
EDPAY_PUBLIC_KEY=pk_test_...
EDPAY_PRIVATE_KEY=sk_test_...
EDPAY_ENVIRONMENT=sandbox
EDPAY_WEBHOOK_SECRET=whsec_...
EDPAY_CURRENCY=MZN
```

### 3. Utilização com Facade no Laravel

```php
use EDPay\Laravel\Facades\Edpay;

// Criar pagamento multi-gateway via Facade
$response = Edpay::checkout()->charge([
    'amount' => 1500.00,
    'currency' => 'MZN',
    'reference' => 'ORDER-10492',
    'channel' => [
        'type' => 'mobile_money',
        'provider' => 'emola',
        'customer_phone' => '+25884xxxxxxx',
        'emola_custom_message' => 'Confirme o pagamento do Pedido #10492',
    ],
]);

return response()->json($response);
```

### 4. Recepção Automática de Webhooks

A rota `/edpay/webhook` é registada automaticamente. Para reagir a eventos, adicione ouvintes no seu `EventServiceProvider`:

```php
use Illuminate\Support\Facades\Event;

Event::listen('edpay.webhook.payment_succeeded', function ($event) {
    $paymentId = $event['data']['id'];
    $orderRef = $event['data']['reference'];

    // Actualize o estado da sua encomenda na base de dados
});
```

---

## Utilização em PHP Puro

```php
use EDPay\EDPayClient;

require_once __DIR__ . '/vendor/autoload.php';

$client = new EDPayClient(
    publicKey: getenv('EDPAY_PUBLIC_KEY'),
    privateKey: getenv('EDPAY_PRIVATE_KEY'),
    environment: 'sandbox' // ou 'production'
);

// 1. Criar link de pagamento
$link = $client->paymentLinks()->create([
    'amount_minor' => 150000, // 1.500,00 MZN em centavos
    'currency' => 'MZN',
    'expires_at' => '2026-12-31T23:59:59Z',
]);

echo $link['data']['url'];

// 2. Validar assinatura de webhook
$webhook = $client->webhooks()->constructEvent(
    payload: file_get_contents('php://input'),
    signatureHeader: $_SERVER['HTTP_X_EDPAY_SIGNATURE'] ?? '',
    secret: getenv('EDPAY_WEBHOOK_SECRET')
);
```

---

## Condições Comerciais

- **Tarifa plana**: 6% transparente em Meticais (MZN).
- **Liquidação bancária**: 2 a 7 dias úteis após a transacção.

---

## Licença

MIT © [Edutecka](https://edutecka.com)
