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
        // 1. Rename dan drop kolom bawaan Laravel
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id', 'user_id');
            $table->renameColumn('password', 'password_hash');
            $table->dropColumn('name');
        });

        // 2. Ubah password_hash menjadi nullable dan tambahkan kolom MarketTrust
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_hash', 255)->nullable()->change();
            $table->string('username', 30)->unique()->after('user_id');
            $table->string('google_id', 255)->nullable()->unique()->after('password_hash');
            $table->string('avatar_url', 500)->nullable()->after('google_id');
            $table->enum('account_type', ['USER', 'ADMIN'])->default('USER')->after('email_verified_at');
            $table->enum('account_status', ['ACTIVE', 'SUSPENDED'])->default('ACTIVE')->after('account_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop kolom MarketTrust, restore name, dan kembalikan nama kolom
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_status',
                'account_type',
                'avatar_url',
                'google_id',
                'username',
            ]);
            $table->string('name')->after('user_id');
            $table->renameColumn('password_hash', 'password');
            $table->renameColumn('user_id', 'id');
        });

        // 2. Kembalikan password menjadi not nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
