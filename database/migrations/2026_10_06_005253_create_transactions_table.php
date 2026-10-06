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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('agreement_id')->unique()->constrained('agreements', 'agreement_id');
            $table->enum('source_type', ['MARKETPLACE', 'FORUM']);
            $table->foreignId('listing_id')->nullable()->constrained('listings', 'listing_id')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('forum_posts', 'post_id')->nullOnDelete();
            $table->enum('status', [
                'WAITING_PAYMENT',
                'PAID',
                'DELIVERED',
                'CONFIRMED',
                'DISPUTED',
                'COMPLETED',
                'REFUNDED',
                'CANCELLED',
            ])->default('WAITING_PAYMENT');
            $table->timestamp('payment_due_at');
            $table->timestamp('delivery_due_at')->nullable();
            $table->timestamp('confirmation_due_at')->nullable();
            $table->text('delivery_note')->nullable();
            $table->string('result_link', 500)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
