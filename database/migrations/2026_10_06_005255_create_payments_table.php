<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('transaction_id')->unique()->constrained('transactions', 'transaction_id');
            $table->string('gateway_order_id', 100)->unique();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'EXPIRED', 'CANCELLED'])->default('PENDING');
            $table->string('gateway_status', 50)->nullable();
            $table->json('latest_payload')->nullable();
            $table->timestamp('last_notification_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
