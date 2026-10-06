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
        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id('evidence_id');
            $table->foreignId('uploaded_by')->constrained('users', 'user_id');
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'payment_id')->nullOnDelete();
            $table->foreignId('delivery_transaction_id')->nullable()->constrained('transactions', 'transaction_id')->nullOnDelete();
            $table->foreignId('complaint_id')->nullable()->constrained('complaints', 'complaint_id')->nullOnDelete();
            $table->foreignId('response_id')->nullable()->constrained('complaint_responses', 'response_id')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('account_reports', 'report_id')->nullOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('settlements', 'settlement_id')->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_url', 500);
            $table->string('mime_type', 100);
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_files');
    }
};
