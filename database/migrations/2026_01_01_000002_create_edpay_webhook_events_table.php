<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit and idempotency table for received EDPay webhook events.
     */
    public function up(): void
    {
        Schema::create('edpay_webhook_events', function (Blueprint $table) {
            $table->string('id', 64)->primary(); // evt_...
            $table->string('event', 64)->index(); // payment.succeeded, payment.failed, etc.
            $table->string('payment_id', 64)->nullable()->index(); // pay_...
            $table->string('status', 32)->default('processed'); // processed, failed, ignored
            $table->json('payload'); // Full copy of the received JSON payload
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Roll back the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('edpay_webhook_events');
    }
};
