<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrações da tabela de pagamentos EDPay.
     */
    public function up(): void
    {
        Schema::create('edpay_payments', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // pay_...
            $table->string('reference', 64)->index(); // EDPT-YYMM00001 ou ref do comerciante
            $table->string('trans_id', 64)->nullable()->index(); // ID da transação no gateway
            $table->decimal('amount', 15, 2); // Montante bruto (ex.: 1500.00)
            $table->string('currency', 3)->default('MZN'); // Código ISO (MZN padrão)
            $table->decimal('fee', 15, 2)->default(0.00); // Tarifa plana 6%
            $table->decimal('net_amount', 15, 2)->default(0.00); // Valor líquido a receber
            $table->string('method', 32); // mpesa, emola, card, apple_pay, google_pay, bank_transfer
            $table->string('status', 32)->index(); // pending, pending_prompt, succeeded, failed, cancelled
            $table->string('customer_id', 64)->nullable()->index();
            $table->string('customer_phone', 32)->nullable(); // +25884xxxxxxx
            $table->string('customer_email', 128)->nullable();
            $table->string('payment_link_id', 64)->nullable()->index(); // plink_... se originado de link
            $table->json('metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverte a migração.
     */
    public function down(): void
    {
        Schema::dropIfExists('edpay_payments');
    }
};
