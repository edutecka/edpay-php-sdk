<?php

namespace EDPay\Laravel;

use EDPay\EDPayClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class EdpayServiceProvider extends ServiceProvider
{
    /**
     * Registo de serviços no contentor de dependências.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/edpay.php', 'edpay');

        $this->app->singleton('edpay', function ($app) {
            $config = $app['config']['edpay'] ?? [];

            return new EDPayClient(
                publicKey: $config['public_key'] ?? '',
                privateKey: $config['private_key'] ?? '',
                environment: $config['environment'] ?? 'sandbox',
                baseUri: $config['base_uri'] ?? null,
                timeout: (int) ($config['timeout'] ?? 60)
            );
        });

        $this->app->alias('edpay', EDPayClient::class);
    }

    /**
     * Inicialização e publicação de recursos (configurações e migrações).
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // 1. Publicação do ficheiro de configuração: php artisan vendor:publish --tag=edpay-config
            $this->publishes([
                __DIR__ . '/../../config/edpay.php' => $this->app->configPath('edpay.php'),
            ], 'edpay-config');

            // 2. Publicação das migrações: php artisan vendor:publish --tag=edpay-migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations/' => $this->app->databasePath('migrations'),
            ], 'edpay-migrations');

            // Carregamento automático de migrações se não foram publicadas
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->registerWebhookRoutes();
    }

    /**
     * Registo opcional da rota padrão para recepção de webhooks.
     */
    protected function registerWebhookRoutes(): void
    {
        $webhookConfig = config('edpay.webhook', []);

        if (!empty($webhookConfig['enabled']) && !empty($webhookConfig['path'])) {
            Route::post($webhookConfig['path'], function (\Illuminate\Http\Request $request) {
                $payload = $request->getContent();
                $signature = $request->header('X-EDPay-Signature') ?? '';
                $secret = config('edpay.webhook_secret') ?? config('edpay.private_key');

                try {
                    $event = app('edpay')->webhooks()->constructEvent($payload, $signature, $secret);

                    // Dispara evento do Laravel para a aplicação ouvir
                    event('edpay.webhook.received', [$event]);
                    event('edpay.webhook.' . str_replace('.', '_', $event['event']), [$event]);

                    return response()->json(['received' => true], 200);
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'invalid_webhook',
                        'message' => $e->getMessage(),
                    ], 400);
                }
            })->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class ?? 'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken']);
        }
    }
}
