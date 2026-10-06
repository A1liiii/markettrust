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
        Schema::create('reputation_events', function (Blueprint $table) {
            $table->id('reputation_event_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('event_type', 50);
            $table->integer('points_delta')->default(0);
            $table->enum('previous_status', ['LEGIT', 'PROBLEMATIC'])->nullable();
            $table->enum('new_status', ['LEGIT', 'PROBLEMATIC'])->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions', 'transaction_id')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('account_reports', 'report_id')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->text('reason');
            $table->string('event_key', 150)->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reputation_events');
    }
};
