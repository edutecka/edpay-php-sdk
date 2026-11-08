<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de auditoria e idempotência de eventos webhook EDPay recebidos.
     */
    public function up(): void
    {
        Schema::create('edpay_webhook_events', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // evt_...
            $table->string('event', 64)->index(); // payment.succeeded, payment.failed, etc.
            $table->string('payment_id', 64)->nullable()->index(); // pay_...
            $table->string('status', 32)->default('processed'); // processed, failed, ignored
            $table->json('payload'); // Cópia integral do payload JSON recebido
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverte a migração.
     */
    public function down(): void
    {
        Schema::dropIfExists('edpay_webhook_events');
    }
};
