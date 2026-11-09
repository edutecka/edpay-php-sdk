<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciais da Plataforma EDPay (Server-to-Server)
    |--------------------------------------------------------------------------
    |
    | Obtenha as suas chaves no Console EDPay (https://pay.edutecka.com/dashboard/api-keys).
    | Utilize pk_test_ e sk_test_ em desenvolvimento e pk_live_ e sk_live_ em produção.
    |
    */
    'public_key' => env('EDPAY_PUBLIC_KEY', ''),
    'private_key' => env('EDPAY_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Ambiente de Operação
    |--------------------------------------------------------------------------
    |
    | Valores suportados: 'sandbox' (teste) ou 'production' (live).
    |
    */
    'environment' => env('EDPAY_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Segredo para Validação de Webhooks HMAC
    |--------------------------------------------------------------------------
    |
    | Chave secreta de assinatura para verificação do cabeçalho X-EDPay-Signature.
    |
    */
    'webhook_secret' => env('EDPAY_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Moeda Padrão
    |--------------------------------------------------------------------------
    |
    | Moeda padrão usada nas configurações do SDK. Taxas e condições
    | financeiras são determinadas pela EDPay e podem ser actualizadas.
    |
    */
    'currency' => env('EDPAY_CURRENCY', 'MZN'),

    /*
    |--------------------------------------------------------------------------
    | URL Base e Timeout da API
    |--------------------------------------------------------------------------
    |
    | Endereço oficial dos serviços EDPay e limite de espera por chamada cURL.
    |
    */
    'base_uri' => env('EDPAY_API_URL', 'https://api-pay.edutecka.com'),
    'timeout' => (int) env('EDPAY_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Rota de Webhook Automática do Laravel
    |--------------------------------------------------------------------------
    |
    | Quando activada, o pacote regista automaticamente a rota para receber webhooks.
    |
    */
    'webhook' => [
        'enabled' => true,
        'path' => env('EDPAY_WEBHOOK_PATH', '/edpay/webhook'),
    ],
];
