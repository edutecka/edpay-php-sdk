<?php

namespace EDPay\Laravel;

use EDPay\EDPayClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class EdpayServiceProvider extends ServiceProvider
{
    /**
     * Register services in the dependency container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/edpay.php', 'edpay');

        $this->app->singleton('edpay', function ($app) {
            $config = $app['config']['edpay'] ?? [];

            return new EDPayClient(
                $config['public_key'] ?? '',
                $config['private_key'] ?? '',
                $config['environment'] ?? 'sandbox',
                $config['base_uri'] ?? null,
                (int) ($config['timeout'] ?? 60)
            );
        });

        $this->app->alias('edpay', EDPayClient::class);
    }

    /**
     * Initialize and publish resources (configuration and migrations).
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // 1. Publish the configuration file: php artisan vendor:publish --tag=edpay-config
            $this->publishes([
                __DIR__ . '/../../config/edpay.php' => $this->app->configPath('edpay.php'),
            ], 'edpay-config');

            // 2. Publish the migrations: php artisan vendor:publish --tag=edpay-migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations/' => $this->app->databasePath('migrations'),
            ], 'edpay-migrations');

            // Automatically load migrations if they have not been published.
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->registerWebhookRoutes();
    }

    /**
     * Optionally register the default route for receiving webhooks.
     */
    protected function registerWebhookRoutes(): void
    {
        $webhookConfig = config('edpay.webhook', []);

        if (!empty($webhookConfig['enabled']) && !empty($webhookConfig['path'])) {
            Route::post($webhookConfig['path'], function (\Illuminate\Http\Request $request) {
                $payload = $request->getContent();
                $signature = $request->header('Payment-Signature') ?? '';
                $secret = config('edpay.webhook_secret', '');

                try {
                    $event = app('edpay')->webhooks()->constructEvent($payload, $signature, $secret);

                    // Dispatch Laravel events for the application to listen for.
                    event('edpay.webhook.received', [$event]);
                    $eventType = $event['type'] ?? $event['event'] ?? null;
                    if (is_string($eventType) && $eventType !== '') {
                        event('edpay.webhook.' . str_replace('.', '_', $eventType), [$event]);
                    }

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
