<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_likes', function (Blueprint $table) {
            $table->id('like_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('forum_posts', 'post_id')->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('forum_comments', 'comment_id')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'post_id']);
            $table->unique(['user_id', 'comment_id']);
        });

        // CHECK constraint: Tepat satu dari post_id dan comment_id bernilai non-null
        DB::statement('ALTER TABLE forum_likes ADD CONSTRAINT check_forum_likes_target CHECK (
            (post_id IS NOT NULL AND comment_id IS NULL) OR
            (post_id IS NULL AND comment_id IS NOT NULL)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_likes');
    }
};
