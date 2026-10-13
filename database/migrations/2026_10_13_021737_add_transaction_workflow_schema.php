<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tujuan payout yang dapat dipakai ulang oleh Seller.
        // Tujuan terpilih tetap disalin ke settlements sebagai snapshot.
        Schema::create('seller_payout_accounts', function (Blueprint $table) {
            $table->id('seller_payout_account_id');
            $table->foreignId('seller_id')->constrained('users', 'user_id');
            $table->enum('destination_type', ['BANK', 'EWALLET', 'QRIS']);
            $table->string('destination_provider', 30)->nullable();
            $table->string('destination_number', 100)->nullable();
            $table->string('destination_holder', 100);
            $table->string('destination_qris_image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['seller_id', 'is_active']);
        });

        // Satu transaksi boleh memiliki beberapa permintaan pembatalan
        // apabila permintaan sebelumnya ditolak. Hanya satu yang boleh
        // disetujui; aturan itu ditegakkan dalam service transaksi.
        Schema::create('transaction_cancellations', function (Blueprint $table) {
            $table->id('cancellation_id');
            $table->foreignId('transaction_id')
                ->constrained('transactions', 'transaction_id');
            $table->foreignId('requested_by')
                ->constrained('users', 'user_id');
            $table->enum('stage', [
                'BEFORE_PAYMENT',
                'AFTER_PAYMENT_BEFORE_DELIVERY',
            ]);
            $table->text('reason')->nullable();
            $table->enum('status', [
                'REQUESTED',
                'APPROVED',
                'REJECTED',
            ])->default('REQUESTED');
            $table->enum('responsible_party', [
                'BUYER',
                'SELLER',
                'PLATFORM',
                'UNDETERMINED',
            ])->nullable();
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });

        // Nominal KEEP dibekukan saat pembatalan diputuskan.
        // fee_basis_amount adalah biaya Midtrans yang menjadi dasar
        // perhitungan, bukan fee layanan MarketTrust pada agreements.
        Schema::create('keep_obligations', function (Blueprint $table) {
            $table->id('keep_obligation_id');
            $table->foreignId('cancellation_id')->unique()
                ->constrained('transaction_cancellations', 'cancellation_id');
            $table->foreignId('seller_id')
                ->constrained('users', 'user_id');
            $table->string('payment_channel_snapshot', 50);
            $table->enum('calculation_rule', [
                'BANK_VA_FLAT',
                'OTHER_TRIPLE_GATEWAY_FEE',
            ]);
            $table->decimal('fee_basis_amount', 12, 2);
            $table->unsignedSmallInteger('multiplier');
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->enum('status', ['OPEN', 'SETTLED'])->default('OPEN');
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });

        // Biaya gateway yang terverifikasi disimpan sebagai data pembayaran.
        // Bisa NULL sampai biayanya diketahui; jangan menebak nilainya.
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('gateway_fee_amount', 12, 2)->nullable();
        });

        // amount yang sudah ada tetap berarti nominal akhir yang
        // benar-benar dicatat sebagai payout atau refund.
        Schema::table('settlements', function (Blueprint $table) {
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->decimal('keep_deducted_amount', 12, 2)->default(0);
            $table->timestamp('payout_due_at')->nullable();
        });

        // Satu payout dapat melunasi beberapa kewajiban KEEP.
        // Satu kewajiban dapat dipotong bertahap dari beberapa payout.
        Schema::create('keep_deductions', function (Blueprint $table) {
            $table->id('keep_deduction_id');
            $table->foreignId('keep_obligation_id')
                ->constrained('keep_obligations', 'keep_obligation_id');
            $table->foreignId('settlement_id')
                ->constrained('settlements', 'settlement_id');
            $table->decimal('amount', 12, 2);
            $table->timestamp('deducted_at');
            $table->timestamps();

            $table->unique(
                ['keep_obligation_id', 'settlement_id'],
                'keep_deduction_per_payout_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keep_deductions');

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn([
                'gross_amount',
                'keep_deducted_amount',
                'payout_due_at',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway_fee_amount');
        });

        Schema::dropIfExists('keep_obligations');
        Schema::dropIfExists('transaction_cancellations');
        Schema::dropIfExists('seller_payout_accounts');
    }
};
