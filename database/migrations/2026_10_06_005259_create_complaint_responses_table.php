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
        Schema::create('complaint_responses', function (Blueprint $table) {
            $table->id('response_id');
            $table->foreignId('complaint_id')->constrained('complaints', 'complaint_id')->cascadeOnDelete();
            $table->foreignId('responder_id')->constrained('users', 'user_id');
            $table->text('response_text');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_responses');
    }
};
