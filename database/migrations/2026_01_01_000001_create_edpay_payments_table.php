<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the EDPay payments table migration.
     */
    public function up(): void
    {
        Schema::create('edpay_payments', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // pay_...
            $table->string('reference', 64)->unique(); // EDPT-YYMM00001 or merchant reference
            $table->string('trans_id', 64)->nullable()->index(); // Gateway transaction ID
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('MZN'); // ISO code (MZN by default)
            $table->unsignedBigInteger('fee_minor')->nullable();
            $table->unsignedBigInteger('net_amount_minor')->nullable();
            $table->string('method', 64)->nullable();
            $table->string('status', 32)->index(); // pending, pending_prompt, succeeded, failed, cancelled
            $table->string('customer_id', 64)->nullable()->index();
            $table->string('customer_phone', 32)->nullable(); // +25884xxxxxxx
            $table->string('customer_email', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Roll back the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('edpay_payments');
    }
};
