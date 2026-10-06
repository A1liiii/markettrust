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
        Schema::create('listings', function (Blueprint $table) {
            $table->id('listing_id');
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants', 'variant_id')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('delivery_method', 100);
            $table->integer('estimated_delivery_value');
            $table->enum('estimated_delivery_unit', ['MINUTE', 'HOUR', 'DAY']);
            $table->enum('status', ['DRAFT', 'ACTIVE', 'CLOSED'])->default('DRAFT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
