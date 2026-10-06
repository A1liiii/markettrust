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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->foreignId('participant_one_id')->constrained('users', 'user_id');
            $table->foreignId('participant_two_id')->constrained('users', 'user_id');
            $table->foreignId('listing_id')->nullable()->constrained('listings', 'listing_id')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('forum_posts', 'post_id')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->unique()->constrained('agreements', 'agreement_id')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
