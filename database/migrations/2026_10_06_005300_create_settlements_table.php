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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id('settlement_id');
            $table->foreignId('transaction_id')->unique()->constrained('transactions', 'transaction_id');
            $table->foreignId('complaint_id')->nullable()->unique()->constrained('complaints', 'complaint_id')->nullOnDelete();
            $table->enum('settlement_type', ['PAYOUT', 'REFUND']);
            $table->enum('destination_type', ['BANK', 'EWALLET', 'QRIS']);
            $table->string('destination_provider', 30)->nullable();
            $table->string('destination_number', 100)->nullable();
            $table->string('destination_holder', 100);
            $table->string('destination_qris_image_url', 500)->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['PLANNED', 'READY', 'PROCESSING', 'COMPLETED'])->default('PLANNED');
            $table->foreignId('recorded_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
