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
        Schema::create('fee_configs', function (Blueprint $table) {
            $table->id('fee_config_id');
            $table->decimal('min_amount', 12, 2);
            $table->decimal('max_amount', 12, 2);
            $table->decimal('fee_percent', 5, 2);
            $table->decimal('flat_fee', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_configs');
    }
};
