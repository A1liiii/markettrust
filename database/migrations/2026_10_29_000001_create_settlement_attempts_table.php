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
        Schema::create('settlement_attempts', function (Blueprint $table) {
            $table->id('attempt_id');
            $table->foreignId('settlement_id')
                ->constrained('settlements', 'settlement_id')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_no')->default(1);
            $table->enum('status', ['FAILED', 'UNKNOWN', 'SUCCESS']);
            $table->enum('failure_category', [
                'NONE',
                'SYSTEM',
                'SELLER_DESTINATION',
                'GATEWAY_UNKNOWN',
            ])->default('NONE');
            $table->enum('destination_type', ['BANK', 'EWALLET', 'QRIS'])->nullable();
            $table->string('destination_provider', 30)->nullable();
            $table->string('destination_number', 100)->nullable();
            $table->string('destination_holder', 100)->nullable();
            $table->string('destination_qris_image_url', 500)->nullable();
            $table->decimal('amount_attempted', 12, 2)->default(0);
            $table->string('reference_no', 100)->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('follow_up_action')->nullable();
            $table->boolean('requires_destination_update')->default(false);
            $table->boolean('is_system_disruption_announced')->default(false);
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            $table->index(['settlement_id', 'attempt_no']);
            $table->index(['settlement_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_attempts');
    }
};
