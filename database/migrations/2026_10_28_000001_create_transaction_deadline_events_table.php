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
        Schema::create('transaction_deadline_events', function (Blueprint $table) {
            $table->id('deadline_event_id');
            $table->foreignId('transaction_id')
                ->constrained('transactions', 'transaction_id')
                ->cascadeOnDelete();
            $table->string('event_type', 50);
            $table->timestamp('previous_deadline')->nullable();
            $table->timestamp('new_deadline')->nullable();
            $table->unsignedInteger('extension_minutes')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('granted_by')->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->boolean('is_flagged_for_admin')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['transaction_id', 'event_type']);
            $table->index('is_flagged_for_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_deadline_events');
    }
};
