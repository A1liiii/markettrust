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
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id('post_id');
            $table->foreignId('author_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->enum('category_code', ['GENERAL', 'WTB', 'WTS']);
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'variant_id')->nullOnDelete();
            $table->string('title', 150);
            $table->text('content');
            $table->json('image_urls')->nullable();
            $table->decimal('price_offer', 12, 2)->nullable();
            $table->enum('status', ['ACTIVE', 'CLOSED', 'HIDDEN'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
