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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id('agreement_id');
            $table->foreignId('created_by')->constrained('users', 'user_id');
            $table->foreignId('buyer_id')->constrained('users', 'user_id');
            $table->foreignId('seller_id')->constrained('users', 'user_id');
            $table->foreignId('variant_id')->constrained('product_variants', 'variant_id');
            $table->foreignId('fee_config_id')->nullable()->constrained('fee_configs', 'fee_config_id');
            $table->string('item_title', 150);
            $table->text('item_description');
            $table->integer('quantity')->default(1);
            $table->decimal('base_price', 12, 2);
            $table->enum('fee_type', ['INCLUDE', 'EXCLUDE']);
            $table->decimal('fee_amount', 12, 2)->nullable();
            $table->decimal('buyer_total', 12, 2)->nullable();
            $table->decimal('seller_net', 12, 2)->nullable();
            $table->string('delivery_method', 100);
            $table->integer('delivery_within_minutes');
            $table->text('agreement_note')->nullable();
            $table->integer('revision_no')->default(1);
            $table->enum('status', ['DRAFT', 'FINALIZED', 'REJECTED', 'CANCELLED'])->default('DRAFT');
            $table->timestamp('buyer_accepted_at')->nullable();
            $table->timestamp('seller_accepted_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users', 'user_id');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
