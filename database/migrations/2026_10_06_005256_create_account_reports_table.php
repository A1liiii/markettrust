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
        Schema::create('account_reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('reported_user_id')->constrained('users', 'user_id');
            $table->foreignId('reporter_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions', 'transaction_id')->nullOnDelete();
            $table->enum('source', ['USER', 'SYSTEM', 'ADMIN']);
            $table->text('reason');
            $table->enum('status', ['OPEN', 'REVIEWING', 'RESOLVED', 'DISMISSED'])->default('OPEN');
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_reports');
    }
};
