<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX seller_payout_accounts_seller_id_is_active_unique ON seller_payout_accounts (seller_id) WHERE is_active = true');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX seller_payout_accounts_seller_id_is_active_unique ON seller_payout_accounts (seller_id) WHERE is_active = 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS seller_payout_accounts_seller_id_is_active_unique');
        }
    }
};
