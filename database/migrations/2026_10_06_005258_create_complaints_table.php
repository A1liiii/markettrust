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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id('complaint_id');
            $table->foreignId('transaction_id')->unique()->constrained('transactions', 'transaction_id');
            $table->foreignId('raised_by')->constrained('users', 'user_id');
            $table->text('reason');
            $table->enum('status', ['OPEN', 'REVIEWING', 'RESOLVED'])->default('OPEN');
            $table->enum('decision', ['PAYOUT', 'REFUND'])->nullable();
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
