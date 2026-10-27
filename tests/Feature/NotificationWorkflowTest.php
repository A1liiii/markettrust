<?php

namespace Tests\Feature;

use App\Contracts\Services\NotificationServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\NotificationEventType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => 'Kategori Digital '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Akun Test '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Test '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createAgreement(User $buyer, User $seller): Agreement
    {
        $variant = $this->createVariant();

        return Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Lisensi Software Pro',
            'item_description' => 'Aktivasi resmi software',
            'quantity' => 1,
            'base_price' => 50000.00,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 5000.00,
            'buyer_total' => 55000.00,
            'seller_net' => 50000.00,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED->value,
        ]);
    }

    protected function createTransaction(User $buyer, User $seller): Transaction
    {
        $agreement = $this->createAgreement($buyer, $seller);

        return Transaction::create([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'status' => TransactionStatus::WAITING_PAYMENT,
            'payment_due_at' => now()->addHour(),
        ]);
    }

    /**
     * TC-22-01: Verifikasi publikasi kontrak notify() untuk seluruh alur transaksi.
     */
    public function test_notification_service_publishes_all_transaction_event_contracts(): void
    {
        $service = app(NotificationServiceInterface::class);
        $this->assertInstanceOf(NotificationService::class, $service);

        $buyer = User::factory()->create(['username' => 'buyer_notif']);
        $seller = User::factory()->create(['username' => 'seller_notif']);
        $admin = User::factory()->admin()->create(['username' => 'admin_notif']);

        $tx = $this->createTransaction($buyer, $seller);

        // 1. Usulan & Revisi
        $notifProposal = $service->notifyProposal($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro', $buyer->username, false);
        $this->assertNotNull($notifProposal);
        $this->assertEquals(NotificationEventType::AGREEMENT_PROPOSED->value, $notifProposal->event_type);
        $this->assertEquals($seller->user_id, $notifProposal->user_id);

        $notifRevision = $service->notifyProposal($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro', $buyer->username, true);
        $this->assertNotNull($notifRevision);
        $this->assertEquals(NotificationEventType::AGREEMENT_REVISED->value, $notifRevision->event_type);

        // 2. Pembayaran & Expiry
        $notifPay = $service->notifyPaymentSuccess($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro');
        $this->assertEquals(NotificationEventType::PAYMENT_SUCCESS->value, $notifPay->event_type);

        $notifExp = $service->notifyPaymentExpired($buyer->user_id, $tx->transaction_id, 'Lisensi Software Pro');
        $this->assertEquals(NotificationEventType::PAYMENT_EXPIRED->value, $notifExp->event_type);

        // 3. Peringatan seller
        $notifWarn = $service->notifySellerDeliveryWarning($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro', 15);
        $this->assertEquals(NotificationEventType::SELLER_DELIVERY_WARNING->value, $notifWarn->event_type);

        // 4. Grace buyer
        $notifGrace = $service->notifyBuyerGracePeriod($buyer->user_id, $tx->transaction_id, 'Lisensi Software Pro');
        $this->assertEquals(NotificationEventType::BUYER_GRACE_WARNING->value, $notifGrace->event_type);

        // 5. Tenggat komplain
        $notifComp = $service->notifyComplaintDeadline($buyer->user_id, $tx->transaction_id, 'Lisensi Software Pro', 20);
        $this->assertEquals(NotificationEventType::COMPLAINT_DEADLINE_WARNING->value, $notifComp->event_type);

        // 6. Keputusan resolusi
        $notifDecide = $service->notifyDisputeDecided($buyer->user_id, $tx->transaction_id, 'Lisensi Software Pro', 'Refund Penuh Disetujui');
        $this->assertEquals(NotificationEventType::DISPUTE_DECIDED->value, $notifDecide->event_type);

        // 7. Pembatalan
        $notifCancel = $service->notifyTransactionCancelled($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro', 'Pembeli membatalkan pesanan');
        $this->assertEquals(NotificationEventType::TRANSACTION_CANCELLED->value, $notifCancel->event_type);

        // 8. KEEP
        $notifKeep = $service->notifyTransactionKeep($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro');
        $this->assertEquals(NotificationEventType::TRANSACTION_KEEP->value, $notifKeep->event_type);

        // 9. Payout & Refund
        $notifPayout = $service->notifyPayoutReady($seller->user_id, $tx->transaction_id, 'Lisensi Software Pro');
        $this->assertEquals(NotificationEventType::PAYOUT_READY->value, $notifPayout->event_type);

        $notifRefund = $service->notifyRefundProcessed($buyer->user_id, $tx->transaction_id, 'Lisensi Software Pro');
        $this->assertEquals(NotificationEventType::REFUND_PROCESSED->value, $notifRefund->event_type);

        // Pastikan hanya pihak terkait yang menjadi penerima
        $this->assertEquals(12, Notification::whereIn('user_id', [$buyer->user_id, $seller->user_id])->count());
        $this->assertEquals(0, Notification::where('user_id', $admin->user_id)->count());
    }

    /**
     * TC-22-02: Status baca - read_at dicatat saat dipilih dan hanya berubah pada notifikasi miliknya.
     */
    public function test_read_at_is_recorded_when_selected_and_redirects_to_transaction(): void
    {
        $buyer = User::factory()->create(['username' => 'buyer_read']);
        $seller = User::factory()->create(['username' => 'seller_read']);
        $tx = $this->createTransaction($buyer, $seller);

        $service = app(NotificationServiceInterface::class);
        $notif = $service->notifyPaymentSuccess($buyer->user_id, $tx->transaction_id, 'Item Game A');

        $this->assertNull($notif->read_at);

        // Buyer membuka notifikasi miliknya
        $response = $this->actingAs($buyer)->get(route('notifications.read', $notif->notification_id));

        $response->assertRedirect(route('transactions.show', $tx->transaction_id));

        $notif->refresh();
        $this->assertNotNull($notif->read_at);
        $this->assertTrue($notif->isRead());
    }

    /**
     * TC-22-03: Tanpa notifikasi - Pengguna tanpa notifikasi melihat tampilan keadaan kosong yang jelas.
     */
    public function test_empty_notification_center_renders_clear_empty_state(): void
    {
        $user = User::factory()->create(['username' => 'empty_user']);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Pusat Notifikasi');
        $response->assertSee('Belum Ada Notifikasi');
        $response->assertSee('Aktivitas seperti kesepakatan baru');
        $response->assertSee('Total:');
        $response->assertSee('0');
    }

    /**
     * TC-22-04: Akses pihak lain - Ditolak jika membuka notifikasi milik orang lain atau rujukan tanpa izin.
     */
    public function test_unauthorized_access_to_other_users_notification_or_referral_is_forbidden(): void
    {
        $userA = User::factory()->create(['username' => 'user_a']);
        $userB = User::factory()->create(['username' => 'user_b']);
        $userC = User::factory()->create(['username' => 'user_c_outsider']);

        $tx = $this->createTransaction($userA, $userB);

        $service = app(NotificationServiceInterface::class);
        $notifA = $service->notifyPaymentSuccess($userA->user_id, $tx->transaction_id, 'Item Game A');

        // 1. User B mencoba membuka notifikasi milik User A -> Ditolak 403
        $response = $this->actingAs($userB)->get(route('notifications.read', $notifA->notification_id));
        $response->assertForbidden();

        // 2. read_at milik User A tidak berubah
        $notifA->refresh();
        $this->assertNull($notifA->read_at);

        // 3. User C memiliki notifikasi yang sengaja mereferensikan transaksi privat milik User A dan B -> Ditolak 403
        $notifC = Notification::create([
            'user_id' => $userC->user_id,
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'PAYMENT_SUCCESS',
            'message' => 'Pesan uji coba',
            'created_at' => now(),
        ]);

        $responseC = $this->actingAs($userC)->get(route('notifications.read', $notifC->notification_id));
        $responseC->assertForbidden();
    }

    /**
     * TC-22-05: Event ulang & idempotensi retry - Peringatan terjadwal dan notifikasi one-time
     * tidak boleh berganda akibat callback atau worker retry.
     */
    public function test_repeated_event_or_callback_does_not_create_duplicate_notifications(): void
    {
        $buyer = User::factory()->create(['username' => 'buyer_dup']);
        $seller = User::factory()->create(['username' => 'seller_dup']);
        $tx = $this->createTransaction($buyer, $seller);

        $service = app(NotificationServiceInterface::class);

        // Panggilan pertama event peringatan seller
        $notif1 = $service->notifySellerDeliveryWarning($seller->user_id, $tx->transaction_id, 'Akun Game', 15);
        $this->assertNotNull($notif1);

        // Panggilan kedua (simulasi job/worker retry 1 jam kemudian)
        $notif2 = $service->notifySellerDeliveryWarning($seller->user_id, $tx->transaction_id, 'Akun Game', 15);

        // Harus mengembalikan notifikasi yang sama tanpa membuat baris baru di database
        $this->assertEquals($notif1->notification_id, $notif2->notification_id);
        $this->assertEquals(
            1,
            Notification::where('user_id', $seller->user_id)
                ->where('transaction_id', $tx->transaction_id)
                ->where('event_type', NotificationEventType::SELLER_DELIVERY_WARNING->value)
                ->count()
        );

        // Panggilan ulang pembayaran (simulasi webhook Midtrans ganda)
        $pay1 = $service->notifyPaymentSuccess($seller->user_id, $tx->transaction_id, 'Akun Game');
        $pay2 = $service->notifyPaymentSuccess($seller->user_id, $tx->transaction_id, 'Akun Game');
        $this->assertEquals($pay1->notification_id, $pay2->notification_id);
        $this->assertEquals(
            1,
            Notification::where('user_id', $seller->user_id)
                ->where('transaction_id', $tx->transaction_id)
                ->where('event_type', NotificationEventType::PAYMENT_SUCCESS->value)
                ->count()
        );
    }

    /**
     * Pengujian filter daftar notifikasi (Semua, Belum Dibaca, Sudah Dibaca).
     */
    public function test_notification_filtering_by_status(): void
    {
        $user = User::factory()->create(['username' => 'filter_user']);

        // 2 notifikasi belum dibaca
        Notification::create([
            'user_id' => $user->user_id,
            'event_type' => 'TRANSACTION_CREATED',
            'message' => 'Notifikasi belum dibaca 1',
            'read_at' => null,
            'created_at' => now()->subMinutes(10),
        ]);
        Notification::create([
            'user_id' => $user->user_id,
            'event_type' => 'PAYMENT_SUCCESS',
            'message' => 'Notifikasi belum dibaca 2',
            'read_at' => null,
            'created_at' => now()->subMinutes(5),
        ]);

        // 1 notifikasi sudah dibaca
        Notification::create([
            'user_id' => $user->user_id,
            'event_type' => 'PAYOUT_READY',
            'message' => 'Notifikasi sudah dibaca 3',
            'read_at' => now()->subMinutes(1),
            'created_at' => now()->subHours(1),
        ]);

        // Filter: unread
        $responseUnread = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'unread']));
        $responseUnread->assertOk();
        $responseUnread->assertSee('Notifikasi belum dibaca 1');
        $responseUnread->assertSee('Notifikasi belum dibaca 2');
        $responseUnread->assertDontSee('Notifikasi sudah dibaca 3');

        // Filter: read
        $responseRead = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'read']));
        $responseRead->assertOk();
        $responseRead->assertDontSee('Notifikasi belum dibaca 1');
        $responseRead->assertDontSee('Notifikasi belum dibaca 2');
        $responseRead->assertSee('Notifikasi sudah dibaca 3');
    }

    /**
     * Pengujian aksi tandai semua sudah dibaca.
     */
    public function test_mark_all_read_updates_only_current_users_notifications(): void
    {
        $userA = User::factory()->create(['username' => 'user_mark_a']);
        $userB = User::factory()->create(['username' => 'user_mark_b']);

        Notification::create([
            'user_id' => $userA->user_id,
            'event_type' => 'TRANSACTION_CREATED',
            'message' => 'Notif A1',
            'read_at' => null,
            'created_at' => now(),
        ]);
        Notification::create([
            'user_id' => $userA->user_id,
            'event_type' => 'PAYMENT_SUCCESS',
            'message' => 'Notif A2',
            'read_at' => null,
            'created_at' => now(),
        ]);
        Notification::create([
            'user_id' => $userB->user_id,
            'event_type' => 'TRANSACTION_CREATED',
            'message' => 'Notif B1',
            'read_at' => null,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($userA)->post(route('notifications.mark-all-read'));
        $response->assertRedirect();

        $this->assertEquals(0, $userA->notifications()->unread()->count());
        $this->assertEquals(1, $userB->notifications()->unread()->count());
    }

    /**
     * Pengujian navbar memuat bell icon dan badge unread counter.
     */
    public function test_navbar_contains_notification_bell_and_unread_counter(): void
    {
        $user = User::factory()->create(['username' => 'nav_user']);

        Notification::create([
            'user_id' => $user->user_id,
            'event_type' => 'TRANSACTION_CREATED',
            'message' => 'Pesanan baru',
            'read_at' => null,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('notifications.index'));
        $response->assertSee('data-cy="nav-notifications"', false);
        $response->assertSee('data-cy="nav-unread-badge"', false);
    }
}
