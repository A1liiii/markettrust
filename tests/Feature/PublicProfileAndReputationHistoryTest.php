<?php

namespace Tests\Feature;

use App\Models\AccountReport;
use App\Models\Agreement;
use App\Models\KeepObligation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\ReputationEvent;
use App\Models\SellerPayoutAccount;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileAndReputationHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper membuat transaksi dan agreement untuk tes cancellation.
     */
    protected function createTransactionWithAgreement(User $buyer, User $seller): Transaction
    {
        $category = ProductCategory::create([
            'category_name' => 'Kategori Digital '.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Produk Digital '.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Standar '.uniqid(),
            'is_active' => true,
        ]);

        $agreement = Agreement::create([
            'variant_id' => $variant->variant_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'created_by' => $buyer->user_id,
            'item_title' => 'Item Digital Tes',
            'item_description' => 'Deskripsi item digital',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_type' => 'EXCLUDE',
            'fee_amount' => 7000,
            'buyer_total' => 107000,
            'seller_net' => 100000,
            'delivery_method' => 'DM_FILE',
            'delivery_within_minutes' => 60,
            'status' => 'FINALIZED',
            'revision_no' => 1,
            'finalized_at' => now(),
        ]);

        return Transaction::create([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => 'FORUM',
            'status' => 'PAID',
            'payment_due_at' => now()->addHour(),
        ]);
    }

    /**
     * 1. Pengguna terautentikasi dapat melihat profil pengguna lain via path /profile/{id} atau query ?user={id}.
     */
    public function test_authenticated_user_can_view_another_user_profile(): void
    {
        $viewer = User::factory()->create([
            'username' => 'buyer_viewer',
            'account_status' => 'ACTIVE',
        ]);

        $seller = User::factory()->create([
            'username' => 'seller_super',
            'account_status' => 'ACTIVE',
        ]);

        // Akses via route param /profile/{id}
        $responseParam = $this->actingAs($viewer)->get('/profile/'.$seller->user_id);
        $responseParam->assertStatus(200);
        $responseParam->assertSee('seller_super');
        $responseParam->assertSee('ID Pengguna: #MT-'.str_pad($seller->user_id, 4, '0', STR_PAD_LEFT));
        $responseParam->assertDontSee('Ubah Foto');
        $responseParam->assertSee('Kembali ke Beranda');

        // Akses via query param /profile?user={id}
        $responseQuery = $this->actingAs($viewer)->get('/profile?user='.$seller->user_id);
        $responseQuery->assertStatus(200);
        $responseQuery->assertSee('seller_super');

        // Akses via query param /profile?username={username}
        $responseUsername = $this->actingAs($viewer)->get('/profile?username=seller_super');
        $responseUsername->assertStatus(200);
        $responseUsername->assertSee('seller_super');
    }

    /**
     * 2. Profil publik menampilkan skor poin reputasi dan badge LEGIT, PROBLEMATIC, atau UNREVIEWED secara akurat.
     */
    public function test_public_profile_displays_reputation_points_and_badge(): void
    {
        $viewer = User::factory()->create(['account_status' => 'ACTIVE']);

        // Akun LEGIT dengan poin positif
        $legitUser = User::factory()->create([
            'username' => 'legit_merchant',
            'account_status' => 'ACTIVE',
        ]);
        ReputationEvent::create([
            'user_id' => $legitUser->user_id,
            'event_type' => ReputationEvent::EVENT_TRANSACTION_COMPLETED,
            'points_delta' => 25,
            'new_status' => 'LEGIT',
            'reason' => 'Transaksi sukses diselesaikan tepat waktu',
            'event_key' => 'tx_success_001',
            'created_at' => now(),
        ]);

        $responseLegit = $this->actingAs($viewer)->get('/profile/'.$legitUser->user_id);
        $responseLegit->assertStatus(200);
        $responseLegit->assertSee('+25');
        $responseLegit->assertSee('Terverifikasi (LEGIT)');

        // Akun PROBLEMATIC dengan poin negatif
        $problemUser = User::factory()->create([
            'username' => 'problematic_trader',
            'account_status' => 'ACTIVE',
        ]);
        ReputationEvent::create([
            'user_id' => $problemUser->user_id,
            'event_type' => ReputationEvent::EVENT_ADMIN_MODERATION,
            'points_delta' => -15,
            'new_status' => 'PROBLEMATIC',
            'reason' => 'Sanksi pelanggaran kesepakatan transaksi',
            'event_key' => 'sanction_001',
            'created_at' => now(),
        ]);

        $responseProblem = $this->actingAs($viewer)->get('/profile/'.$problemUser->user_id);
        $responseProblem->assertStatus(200);
        $responseProblem->assertSee('-15');
        $responseProblem->assertSee('Perlu Perhatian (PROBLEMATIC)');

        // Akun UNREVIEWED baru
        $newUser = User::factory()->create([
            'username' => 'brand_new_user',
            'account_status' => 'ACTIVE',
        ]);

        $responseNew = $this->actingAs($viewer)->get('/profile/'.$newUser->user_id);
        $responseNew->assertStatus(200);
        $responseNew->assertSee('0');
        $responseNew->assertSee('Belum Ditinjau');
    }

    /**
     * 3. Profil publik menampilkan daftar riwayat peristiwa reputasi dan kartu edukasi indikator.
     */
    public function test_public_profile_displays_reputation_events_history_and_indicator_meanings(): void
    {
        $viewer = User::factory()->create(['account_status' => 'ACTIVE']);
        $target = User::factory()->create([
            'username' => 'active_trader',
            'account_status' => 'ACTIVE',
        ]);

        ReputationEvent::create([
            'user_id' => $target->user_id,
            'event_type' => ReputationEvent::EVENT_TRANSACTION_COMPLETED,
            'points_delta' => 10,
            'new_status' => 'LEGIT',
            'reason' => 'Transaksi escrow #10 diselesaikan tepat waktu',
            'event_key' => 'event_tx_10',
            'created_at' => now()->subDay(),
        ]);

        ReputationEvent::create([
            'user_id' => $target->user_id,
            'event_type' => ReputationEvent::EVENT_ADMIN_MODERATION,
            'points_delta' => -5,
            'reason' => 'Peringatan keterlambatan penyerahan berkas',
            'event_key' => 'event_warning_1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get('/profile/'.$target->user_id);

        $response->assertStatus(200);
        $response->assertSee('Riwayat Peristiwa Reputasi');
        $response->assertSee('Transaksi Escrow Sukses');
        $response->assertSee('+10 Poin');
        $response->assertSee('Transaksi escrow #10 diselesaikan tepat waktu');
        $response->assertSee('Keputusan Moderasi Admin');
        $response->assertSee('-5 Poin');
        $response->assertSee('Peringatan keterlambatan penyerahan berkas');

        // Panduan Indikator Reputasi
        $response->assertSee('Panduan Indikator Reputasi');
        $response->assertSee('Terverifikasi');
        $response->assertSee('Perlu Perhatian');
        $response->assertSee('Belum Ditinjau');
        $response->assertSee('Poin Dinamis');
    }

    /**
     * 4. Profil publik menampilkan empty state ramah saat akun belum memiliki riwayat peristiwa reputasi.
     */
    public function test_public_profile_displays_empty_state_when_no_reputation_events_exist(): void
    {
        $viewer = User::factory()->create(['account_status' => 'ACTIVE']);
        $emptyTarget = User::factory()->create([
            'username' => 'clean_profile_user',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($viewer)->get('/profile/'.$emptyTarget->user_id);

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Riwayat Peristiwa Reputasi');
        $response->assertSee('Akun ini belum memiliki catatan evaluasi transaksi atau tindakan kepatuhan');
        $response->assertDontSee('data-cy="table-reputation-events"', false);
    }

    /**
     * 5. Profil publik menyembunyikan data sensitif privat (rekening payout, password status, Google ID, email) dari pengguna lain.
     */
    public function test_public_profile_strictly_hides_private_sensitive_data_from_other_users(): void
    {
        $viewer = User::factory()->create([
            'username' => 'curious_buyer',
            'account_status' => 'ACTIVE',
        ]);

        $targetUser = User::factory()->create([
            'username' => 'private_seller',
            'email' => 'private_secret_email_99@example.com',
            'google_id' => 'google_sub_hidden_123',
            'password_hash' => bcrypt('secret123'),
            'account_status' => 'ACTIVE',
        ]);

        // Rekening payout rahasia seller
        SellerPayoutAccount::create([
            'seller_id' => $targetUser->user_id,
            'destination_type' => 'BANK',
            'destination_provider' => 'BANK_BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'John Doe Private',
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer)->get('/profile/'.$targetUser->user_id);

        $response->assertStatus(200);
        // Email privat tidak ditampilkan kepada orang lain
        $response->assertDontSee('private_secret_email_99@example.com');
        // Rekening payout tidak dibocorkan
        $response->assertDontSee('1234567890');
        $response->assertDontSee('BANK_BCA');
        $response->assertDontSee('Tujuan Payout Seller');
        // Bagian keamanan akun privat tidak ditampilkan
        $response->assertDontSee('Koneksi Akun Google');
        $response->assertDontSee('Kata Sandi (Password) Lokal');
        $response->assertDontSee('Ubah Foto');
        $response->assertDontSee('Rekening Payout');
    }

    /**
     * 6. Profil publik tidak membocorkan laporan akun yang belum terbukti (status OPEN / REVIEWING).
     */
    public function test_public_profile_does_not_reveal_unproven_account_reports(): void
    {
        $viewer = User::factory()->create(['account_status' => 'ACTIVE']);
        $reporter = User::factory()->create(['account_status' => 'ACTIVE']);
        $reportedUser = User::factory()->create([
            'username' => 'reported_trader',
            'account_status' => 'ACTIVE',
        ]);

        // Laporan belum ditinjau / masih OPEN
        AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => $reporter->user_id,
            'source' => 'USER',
            'reason' => 'Tuduhan sepihak akun palsu atau tidak responsif',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($viewer)->get('/profile/'.$reportedUser->user_id);

        $response->assertStatus(200);
        $response->assertDontSee('Tuduhan sepihak akun palsu atau tidak responsif');
        $response->assertDontSee('OPEN');
    }

    /**
     * 7. Profil publik menampilkan ringkasan catatan pembatalan sah dan status kewajiban KEEP tanpa dokumen privat.
     */
    public function test_public_profile_displays_reviewed_cancellation_and_keep_summary_without_private_evidence(): void
    {
        $viewer = User::factory()->create(['account_status' => 'ACTIVE']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create([
            'username' => 'target_seller',
            'account_status' => 'ACTIVE',
        ]);

        $transaction = $this->createTransactionWithAgreement($buyer, $seller);

        // Pembatalan pasca-bayar yang disetujui admin dan dibebankan ke SELLER
        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Penjual tidak merespons selama berjam-jam',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'decision_note' => 'Disetujui admin karena seller tidak aktif',
            'reviewed_at' => now(),
        ]);

        // Kewajiban KEEP terbuka untuk seller
        KeepObligation::create([
            'cancellation_id' => $cancellation->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'BCA_VA',
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 4000,
            'multiplier' => 1,
            'amount' => 4000,
            'remaining_amount' => 4000,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        $response = $this->actingAs($viewer)->get('/profile/'.$seller->user_id);

        $response->assertStatus(200);
        $response->assertSee('Catatan Kepatuhan Transaksi');
        $response->assertSee('1x Keputusan Admin');
        $response->assertSee('1 Tagihan Terbuka');
        // Tidak membocorkan catatan privat internal chat / alasan sensitif
        $response->assertDontSee('Penjual tidak merespons selama berjam-jam');
    }

    /**
     * 8. Pemilik akun sendiri tetap dapat melihat seluruh data privat dan pengaturan rekeningnya.
     */
    public function test_own_profile_allows_viewing_private_payout_and_security_settings(): void
    {
        $user = User::factory()->create([
            'username' => 'my_own_account',
            'email' => 'my_private_email@example.com',
            'account_status' => 'ACTIVE',
        ]);

        SellerPayoutAccount::create([
            'seller_id' => $user->user_id,
            'destination_type' => 'BANK',
            'destination_provider' => 'BANK_BRI',
            'destination_number' => '9876543210',
            'destination_holder' => 'My Real Name',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('my_own_account');
        $response->assertSee('my_private_email@example.com');
        $response->assertSee('Ubah Foto');
        $response->assertSee('Rekening Payout');
        $response->assertSee('Keamanan Akun');
        $response->assertSee('Tujuan Payout Seller');
        $response->assertSee('BANK_BRI');
    }

    /**
     * 9. Tamu (guest) yang belum login dialihkan ke halaman login saat mencoba melihat profil.
     */
    public function test_guest_cannot_view_profile_and_redirects_to_login(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        // Profil umum
        $responseGuest = $this->get('/profile');
        $responseGuest->assertRedirect('/login');

        // Profil pengguna spesifik
        $responseTarget = $this->get('/profile/'.$user->user_id);
        $responseTarget->assertRedirect('/login');
    }

    /**
     * 10. Mengakses profil pengguna yang tidak terdaftar menghasilkan response 404.
     */
    public function test_non_existent_user_profile_returns_404(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        $responseNonExistentParam = $this->actingAs($user)->get('/profile/9999999');
        $responseNonExistentParam->assertStatus(404);

        $responseNonExistentQuery = $this->actingAs($user)->get('/profile?user=9999999');
        $responseNonExistentQuery->assertStatus(404);

        $responseNonExistentUsername = $this->actingAs($user)->get('/profile?username=non_existent_user_xyz');
        $responseNonExistentUsername->assertStatus(404);
    }
}
