<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();


      $adminUser = \App\Models\User::create([
        'username' => 'adm_mta11_1793382593296',
        'email' => 'adm_mta11_1793382593296@markettrust.test',
        'password_hash' => bcrypt('Password123!'),
        'account_type' => 'ADMIN',
        'account_status' => 'ACTIVE',
      ]);

      $sellerUser = \App\Models\User::create([
        'username' => 'seller_mta11_1793382593296',
        'email' => 'seller_mta11_1793382593296@markettrust.test',
        'password_hash' => bcrypt('Password123!'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      $buyerUser = \App\Models\User::create([
        'username' => 'buyer_mta11_1793382593296',
        'email' => 'buyer_mta11_1793382593296@markettrust.test',
        'password_hash' => bcrypt('Password123!'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      $unrelatedUser = \App\Models\User::create([
        'username' => 'other_mta11_1793382593296',
        'email' => 'other_mta11_1793382593296@markettrust.test',
        'password_hash' => bcrypt('Password123!'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      // Rekening Payout Seller
      \App\Models\SellerPayoutAccount::create([
        'seller_id' => $sellerUser->user_id,
        'destination_type' => 'BANK',
        'destination_provider' => 'BCA',
        'destination_number' => '7788990011',
        'destination_holder' => 'Seller MT-A11 Test',
        'is_active' => true,
      ]);

      $category = \App\Models\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Digital MT-A11'],
        ['is_active' => true]
      );

      $product = \App\Models\Product::firstOrCreate(
        ['product_name' => 'Produk Lisensi MT-A11'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \App\Models\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian Standard MT-A11'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \App\Models\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Software MT-A11 E2E',
        'description' => 'Lisensi uji riwayat transaksi dan audit log',
        'price' => 100000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 30,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      // 1. Transaksi Auto-Confirm (CONFIRMED oleh sistem, ada deadline event & settlement payout)
      $ag1 = \App\Models\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Lisensi Auto-Confirm E2E',
        'item_description' => 'Transaksi untuk uji auto-confirm dan settlement',
        'quantity' => 1,
        'base_price' => 100000.00,
        'fee_type' => \App\Enums\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 105000.00,
        'seller_net' => 100000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Uji Auto-Confirm',
        'revision_no' => 1,
        'status' => \App\Enums\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx1 = \App\Models\Transaction::create([
        'agreement_id' => $ag1->agreement_id,
        'source_type' => \App\Enums\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \App\Enums\TransactionStatus::CONFIRMED,
        'payment_due_at' => now()->subHours(3),
        'delivery_due_at' => now()->subHours(2),
        'delivered_at' => now()->subHours(2),
        'confirmation_due_at' => now()->subHours(1),
        'confirmed_at' => now()->subHours(1),
        'delivery_note' => 'Akses lisensi: E2E-AUTO-CONFIRM-999. Tautan aktif.',
        'result_link' => 'https://example.com/e2e-access',
      ]);

      \App\Models\TransactionStatusHistory::create([
        'transaction_id' => $tx1->transaction_id,
        'from_status' => 'PAID',
        'to_status' => 'DELIVERED',
        'changed_by' => $sellerUser->user_id,
        'changed_at' => now()->subHours(2),
        'note' => 'Seller menyerahkan hasil pesanan',
      ]);

      \App\Models\TransactionStatusHistory::create([
        'transaction_id' => $tx1->transaction_id,
        'from_status' => 'DELIVERED',
        'to_status' => 'CONFIRMED',
        'changed_by' => null, // Auto-confirm sistem
        'changed_at' => now()->subHours(1),
        'note' => 'Dikonfirmasi otomatis oleh sistem (grace period 30 menit berakhir)',
      ]);

      \App\Models\TransactionDeadlineEvent::create([
        'transaction_id' => $tx1->transaction_id,
        'event_type' => 'BUYER_GRACE_PERIOD',
        'previous_deadline' => now()->subHours(1)->subMinutes(30),
        'new_deadline' => now()->subHours(1),
        'extension_minutes' => 30,
        'reason' => 'Masa sanggah 30 menit otomatis sebelum auto-confirm',
        'granted_by' => null,
        'created_at' => now()->subHours(1)->subMinutes(30),
      ]);

      \App\Models\Settlement::create([
        'transaction_id' => $tx1->transaction_id,
        'settlement_type' => 'PAYOUT',
        'status' => 'COMPLETED',
        'gross_amount' => 100000.00,
        'keep_deducted_amount' => 0.00,
        'amount' => 100000.00,
        'destination_type' => 'BANK',
        'destination_provider' => 'BCA',
        'destination_number' => '7788990011',
        'destination_holder' => 'Seller MT-A11 Test',
        'reference_no' => 'TRF-PAYOUT-8899',
        'recorded_by' => $adminUser->user_id,
        'completed_at' => now()->subMinutes(30),
      ]);

      // 2. Transaksi Manual Confirm (CONFIRMED oleh pembeli)
      $ag2 = \App\Models\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Lisensi Manual-Confirm E2E',
        'item_description' => 'Transaksi uji konfirmasi manual',
        'quantity' => 1,
        'base_price' => 100000.00,
        'fee_type' => \App\Enums\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 105000.00,
        'seller_net' => 100000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Uji Manual Confirm',
        'revision_no' => 1,
        'status' => \App\Enums\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx2 = \App\Models\Transaction::create([
        'agreement_id' => $ag2->agreement_id,
        'source_type' => \App\Enums\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \App\Enums\TransactionStatus::CONFIRMED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(40),
        'confirmed_at' => now()->subMinutes(10),
        'delivery_note' => 'Lisensi manual token: MANUAL-CONFIRM-TOKEN',
      ]);

      \App\Models\TransactionStatusHistory::create([
        'transaction_id' => $tx2->transaction_id,
        'from_status' => 'DELIVERED',
        'to_status' => 'CONFIRMED',
        'changed_by' => $buyerUser->user_id,
        'changed_at' => now()->subMinutes(10),
        'note' => 'Pembeli mengonfirmasi pesanan sesuai',
      ]);

      // 3. Transaksi Batal + Komplain + Beban KEEP Penjual
      $ag3 = \App\Models\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Lisensi Komplain Batal KEEP E2E',
        'item_description' => 'Transaksi uji komplain dan beban KEEP',
        'quantity' => 1,
        'base_price' => 150000.00,
        'fee_type' => \App\Enums\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 155000.00,
        'seller_net' => 150000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Uji KEEP',
        'revision_no' => 1,
        'status' => \App\Enums\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx3 = \App\Models\Transaction::create([
        'agreement_id' => $ag3->agreement_id,
        'source_type' => \App\Enums\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \App\Enums\TransactionStatus::CANCELLED,
        'payment_due_at' => now()->subHours(2),
      ]);

      $complaint = \App\Models\Complaint::create([
        'transaction_id' => $tx3->transaction_id,
        'raised_by' => $buyerUser->user_id,
        'reason' => 'Lisensi tidak valid dan akun penjual tidak merespons.',
        'status' => 'RESOLVED',
        'decision' => 'REFUND',
        'decision_reason' => 'Mediasi memenangkan pembeli.',
        'decided_by' => $adminUser->user_id,
        'decided_at' => now()->subMinutes(30),
      ]);

      $cancellation = \App\Models\TransactionCancellation::create([
        'transaction_id' => $tx3->transaction_id,
        'requested_by' => $buyerUser->user_id,
        'stage' => 'AFTER_PAYMENT_BEFORE_DELIVERY',
        'reason' => 'Pembatalan akibat lisensi cacat sesuai hasil mediasi',
        'status' => 'APPROVED',
        'responsible_party' => 'SELLER',
        'reviewed_by' => $adminUser->user_id,
        'decision_note' => 'Persetujuan pembatalan oleh admin',
        'reviewed_at' => now()->subMinutes(20),
      ]);

      $keep = \App\Models\KeepObligation::create([
        'cancellation_id' => $cancellation->cancellation_id,
        'seller_id' => $sellerUser->user_id,
        'payment_channel_snapshot' => 'bank_transfer',
        'calculation_rule' => \App\Models\KeepObligation::RULE_BANK_VA_FLAT,
        'fee_basis_amount' => 4000.00,
        'multiplier' => 3,
        'amount' => 12000.00,
        'remaining_amount' => 12000.00,
        'status' => \App\Models\KeepObligation::STATUS_OPEN,
      ]);

      // 4. Catatan Audit Log
      $log1 = \App\Models\AuditLog::create([
        'actor_id' => $adminUser->user_id,
        'action' => 'TRANSACTION_CANCEL_APPROVED',
        'entity_type' => 'cancellation',
        'entity_id' => $cancellation->cancellation_id,
        'old_value' => ['status' => 'PENDING'],
        'new_value' => ['status' => 'APPROVED', 'decision' => 'APPROVE_REFUND_FULL', 'responsible_party' => 'SELLER'],
        'created_at' => now()->subMinutes(20),
      ]);

      $log2 = \App\Models\AuditLog::create([
        'actor_id' => null, // Sistem
        'action' => 'TRANSACTION_AUTO_CONFIRMED',
        'entity_type' => 'transaction',
        'entity_id' => $tx1->transaction_id,
        'old_value' => ['status' => 'DELIVERED'],
        'new_value' => ['status' => 'CONFIRMED', 'auto_confirmed' => true],
        'created_at' => now()->subHours(1),
      ]);

      $log3 = \App\Models\AuditLog::create([
        'actor_id' => $sellerUser->user_id,
        'action' => 'DELIVERY_DEADLINE_EXTENDED',
        'entity_type' => 'transaction',
        'entity_id' => $tx1->transaction_id,
        'old_value' => ['delivery_due_at' => '2026-10-31 10:00:00'],
        'new_value' => ['delivery_due_at' => '2026-10-31 11:00:00', 'added_minutes' => 60],
        'created_at' => now()->subHours(2),
      ]);

      $log4 = \App\Models\AuditLog::create([
        'actor_id' => $adminUser->user_id,
        'action' => 'RECORD_PAYOUT_COMPLETED',
        'entity_type' => 'settlement',
        'entity_id' => 8899,
        'old_value' => ['status' => 'READY'],
        'new_value' => ['status' => 'COMPLETED', 'transfer_reference' => 'TRF-PAYOUT-8899'],
        'created_at' => now()->subMinutes(15),
      ]);

      echo json_encode([
        'txAutoConfirmId' => $tx1->transaction_id,
        'txManualConfirmId' => $tx2->transaction_id,
        'txCancelledKeepId' => $tx3->transaction_id,
        'cancelLogId' => $log1->log_id,
        'autoConfirmLogId' => $log2->log_id,
        'deadlineLogId' => $log3->log_id,
        'payoutLogId' => $log4->log_id,
      ]);
    
