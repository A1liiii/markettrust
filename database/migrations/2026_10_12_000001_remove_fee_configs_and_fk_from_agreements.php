<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menghapus konfigurasi fee berbasis database (tabel fee_configs) dan relasi foreign key
     * agreements.fee_config_id karena perhitungan fee kini didefinisikan murni melalui kode aplikasi.
     */
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            if (Schema::hasColumn('agreements', 'fee_config_id')) {
                $table->dropConstrainedForeignId('fee_config_id');
            }
        });

        Schema::dropIfExists('fee_configs');
    }

    /**
     * Reverse the migrations.
     *
     * CATATAN BATAS ROLLBACK:
     * Rollback migration ini hanya dapat merekonstruksi struktur tabel fee_configs dan kolom
     * agreements.fee_config_id. Data konfigurasi lama yang telah dihapus TIDAK DAPAT dipulihkan
     * secara otomatis dari rollback ini.
     */
    public function down(): void
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

        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignId('fee_config_id')
                ->nullable()
                ->after('variant_id')
                ->constrained('fee_configs', 'fee_config_id');
        });
    }
};
