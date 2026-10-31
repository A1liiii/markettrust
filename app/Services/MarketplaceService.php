<?php

namespace App\Services;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Contracts\Services\MarketplaceServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;

class MarketplaceService implements MarketplaceServiceInterface
{
    public function __construct(
        protected FeeCalculationServiceInterface $feeCalculationService,
        protected NotificationServiceInterface $notificationService,
        protected PayoutDestinationServiceInterface $payoutDestinationService,
        protected ConversationService $conversationService,
    ) {}

    /**
     * Cari listing aktif dengan filter bertingkat kategori-produk-varian dan pencarian teks.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchListings(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Listing::query()
            ->active()
            ->with(['variant.product.category', 'seller']);

        // 1. Pencarian Kata Kunci (Judul, Deskripsi, Varian, Produk)
        $q = trim((string) ($filters['q'] ?? ''));
        if (! empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('variant', function ($vq) use ($q) {
                        $vq->where('variant_name', 'like', "%{$q}%")
                            ->orWhereHas('product', fn ($pq) => $pq->where('product_name', 'like', "%{$q}%"));
                    });
            });
        }

        // 2. Filter Bertingkat yang Konsisten (Kategori -> Produk -> Varian)
        $categoryId = ! empty($filters['category_id']) ? (int) $filters['category_id'] : null;
        $productId = ! empty($filters['product_id']) ? (int) $filters['product_id'] : null;
        $variantId = ! empty($filters['variant_id']) ? (int) $filters['variant_id'] : null;

        if ($variantId) {
            $query->where('variant_id', $variantId);
        } elseif ($productId) {
            $query->whereHas('variant', function ($vq) use ($productId) {
                $vq->where('product_id', $productId);
            });
        } elseif ($categoryId) {
            $query->whereHas('variant.product', function ($pq) use ($categoryId) {
                $pq->where('category_id', $categoryId);
            });
        }

        // 3. Pengurutan
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->latest('listing_id'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Hitung statistik informasi pasar produk (UC05) untuk varian tertentu.
     *
     * @return array{
     *     demand: int,
     *     supply: int,
     *     active_listings_count: int,
     *     wts_count: int,
     *     avg_price: ?float,
     *     sample_count: int,
     *     has_price_data: bool,
     *     period: string,
     *     period_label: string,
     *     period_days: ?int
     * }
     */
    public function getProductMarketStats(int $variantId, ?string $period = '30d'): array
    {
        $normalizedPeriod = match (strtolower(trim((string) $period))) {
            '7d' => '7d',
            '90d' => '90d',
            'all' => 'all',
            default => '30d',
        };

        $periodLabel = match ($normalizedPeriod) {
            '7d' => '7 Hari Terakhir',
            '90d' => '90 Hari Terakhir',
            'all' => 'Semua Waktu',
            default => '30 Hari Terakhir',
        };

        $periodDays = match ($normalizedPeriod) {
            '7d' => 7,
            '90d' => 90,
            'all' => null,
            default => 30,
        };

        $startDate = $periodDays !== null ? now()->subDays($periodDays) : null;

        // Demand: jumlah WTB aktif pada varian yang sama
        $demand = ForumPost::query()
            ->where('variant_id', $variantId)
            ->where('category_code', 'WTB')
            ->where('status', 'ACTIVE')
            ->count();

        // Listing aktif pada varian yang sama
        $activeListingsCount = Listing::query()
            ->where('variant_id', $variantId)
            ->where('status', 'ACTIVE')
            ->count();

        // WTS aktif pada varian yang sama
        $wtsCount = ForumPost::query()
            ->where('variant_id', $variantId)
            ->where('category_code', 'WTS')
            ->where('status', 'ACTIVE')
            ->count();

        $supply = $activeListingsCount + $wtsCount;

        // Rata-rata harga dari transaksi yang telah selesai (COMPLETED) pada periode yang dipilih
        $txQuery = DB::table('transactions')
            ->join('agreements', 'transactions.agreement_id', '=', 'agreements.agreement_id')
            ->where('transactions.status', 'COMPLETED')
            ->where('agreements.variant_id', $variantId);

        if ($startDate !== null) {
            $txQuery->where('transactions.created_at', '>=', $startDate);
        }

        $txStats = $txQuery
            ->selectRaw('COUNT(transactions.transaction_id) as sample_count, AVG(agreements.base_price) as avg_price')
            ->first();

        $sampleCount = (int) ($txStats?->sample_count ?? 0);
        $avgPrice = $sampleCount > 0 ? (float) round((float) $txStats->avg_price, 2) : null;

        return [
            'demand' => $demand,
            'supply' => $supply,
            'active_listings_count' => $activeListingsCount,
            'wts_count' => $wtsCount,
            'avg_price' => $avgPrice,
            'sample_count' => $sampleCount,
            'has_price_data' => $sampleCount > 0,
            'period' => $normalizedPeriod,
            'period_label' => $periodLabel,
            'period_days' => $periodDays,
        ];
    }

    /**
     * Periksa ketersediaan tujuan payout aktif untuk seller.
     */
    public function validateSellerPayoutAvailable(User $seller): bool
    {
        // 1. Periksa tabel seller_payout_accounts jika ada
        if (Schema::hasTable('seller_payout_accounts')) {
            $hasActiveAccount = DB::table('seller_payout_accounts')
                ->where('seller_id', $seller->user_id)
                ->where('is_active', true)
                ->exists();

            if ($hasActiveAccount) {
                return true;
            }
        }

        // 2. Fallback periksa riwayat settlement pengguna via service payout
        $destination = $this->payoutDestinationService->getDestinationForUser($seller);
        if ($destination && $this->payoutDestinationService->validateDestination($destination)) {
            return true;
        }

        return false;
    }

    /**
     * Eksekusi alur checkout marketplace (UC09) secara aman dan atomik.
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function checkout(User $buyer, Listing $listing, string $submissionToken, float $expectedPrice): Transaction
    {
        // 1. Larangan membeli listing milik sendiri
        if ($buyer->user_id === $listing->seller_id) {
            throw new InvalidArgumentException('Anda tidak dapat membeli item pada listing milik Anda sendiri.');
        }

        if (! $buyer->isActive()) {
            throw new InvalidArgumentException('Akun Anda sedang dinonaktifkan.');
        }

        // 2. Proteksi Idempotency Token
        $tokenCacheKey = "marketplace_checkout_token_{$buyer->user_id}_{$submissionToken}";
        if (Cache::has($tokenCacheKey)) {
            $existingTxId = Cache::get($tokenCacheKey);
            $existing = Transaction::with(['agreement.buyer', 'agreement.seller'])->find($existingTxId);
            if ($existing) {
                return $existing;
            }
        }

        // 3. Atomic Lock Per Buyer & Listing untuk Mencegah Request Bersamaan / Double-Click
        $lockKey = "lock_checkout_{$buyer->user_id}_{$listing->listing_id}";
        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(5);

            // Double check idempotency setelah acquire lock
            if (Cache::has($tokenCacheKey)) {
                $existingTxId = Cache::get($tokenCacheKey);
                $existing = Transaction::with(['agreement.buyer', 'agreement.seller'])->find($existingTxId);
                if ($existing) {
                    return $existing;
                }
            }

            return DB::transaction(function () use ($buyer, $listing, $tokenCacheKey, $expectedPrice) {
                // Ambil data fresh dari database dengan row-level lock
                $freshListing = Listing::with(['seller', 'variant.product'])
                    ->where('listing_id', $listing->listing_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Cek status listing
                if ($freshListing->status !== 'ACTIVE') {
                    throw new InvalidArgumentException('Penawaran listing ini sudah tidak aktif atau telah ditutup.');
                }

                // Cek apakah varian dan produk master masih aktif
                if (! $freshListing->variant || ! $freshListing->variant->is_active || ! $freshListing->variant->product || ! $freshListing->variant->product->is_active) {
                    throw new InvalidArgumentException('Katalog produk untuk listing ini sedang dinonaktifkan.');
                }

                // Cek perbedaan harga setelah preview
                $currentPrice = (float) $freshListing->price;
                if (abs($currentPrice - $expectedPrice) > 0.01) {
                    throw new InvalidArgumentException(sprintf(
                        'Harga penawaran listing telah diperbarui oleh penjual menjadi Rp%s. Silakan tinjau kembali rincian kesepakatan terbaru.',
                        number_format($currentPrice, 0, ',', '.')
                    ));
                }

                // Hitung rincian fee resmi Marketplace (selalu EXCLUDE, ditanggung Buyer)
                $feeCalc = $this->feeCalculationService->calculateForMarketplace($currentPrice);

                // Buat kesepakatan final (Agreement)
                $agreement = Agreement::create([
                    'created_by' => $buyer->user_id,
                    'buyer_id' => $buyer->user_id,
                    'seller_id' => $freshListing->seller_id,
                    'variant_id' => $freshListing->variant_id,
                    'item_title' => $freshListing->title,
                    'item_description' => $freshListing->description,
                    'quantity' => 1,
                    'base_price' => $currentPrice,
                    'fee_type' => FeeType::EXCLUDE->value,
                    'fee_amount' => $feeCalc->feeAmount,
                    'buyer_total' => $feeCalc->buyerTotal,
                    'seller_net' => $feeCalc->sellerNet,
                    'delivery_method' => $freshListing->delivery_method,
                    'delivery_within_minutes' => $freshListing->estimated_minutes,
                    'agreement_note' => 'Kesepakatan otomatis terbentuk dari pembelian langsung Marketplace.',
                    'revision_no' => 1,
                    'status' => AgreementStatus::FINALIZED->value,
                    'buyer_accepted_at' => now(),
                    'seller_accepted_at' => now(),
                    'finalized_at' => now(),
                ]);

                // Buat transaksi menunggu pembayaran (tenggat 1 jam dari finalisasi)
                $transaction = Transaction::create([
                    'agreement_id' => $agreement->agreement_id,
                    'source_type' => TransactionSourceType::MARKETPLACE->value,
                    'listing_id' => $freshListing->listing_id,
                    'status' => TransactionStatus::WAITING_PAYMENT->value,
                    'payment_due_at' => now()->addHour(),
                    'delivery_due_at' => null,
                ]);

                // Inisialisasi percakapan untuk lembar kesepakatan final (UC12)
                $conversation = $this->conversationService->startWithAgreement(
                    $buyer,
                    $freshListing->seller,
                    $agreement->agreement_id
                );

                // Kirim pesan sistem awal pembeli
                $this->conversationService->sendMessage(
                    $conversation,
                    $buyer,
                    sprintf(
                        "Halo @%s! Saya telah membeli item '%s' seharga Rp%s melalui Marketplace. Transaksi #%s telah dibentuk dan menunggu pembayaran escrow.",
                        $freshListing->seller->username,
                        $freshListing->title,
                        number_format($currentPrice, 0, ',', '.'),
                        $transaction->transaction_id
                    )
                );

                // Catat status awal ke riwayat
                TransactionStatusHistory::create([
                    'transaction_id' => $transaction->transaction_id,
                    'from_status' => null,
                    'to_status' => TransactionStatus::WAITING_PAYMENT->value,
                    'changed_by' => $buyer->user_id,
                    'note' => 'Transaksi dimulai dari Marketplace. Menunggu pembayaran escrow.',
                    'changed_at' => now(),
                ]);

                // Kunci token idempotency selama 2 jam
                Cache::put($tokenCacheKey, $transaction->transaction_id, now()->addHours(2));

                return $transaction;
            });
        } finally {
            optional($lock)->release();
        }

        // Kirim notifikasi transaksi baru kepada seller dan buyer setelah commit
        if (isset($transaction)) {
            $this->notificationService->notify(
                $listing->seller_id,
                $transaction->transaction_id,
                'TRANSACTION_CREATED',
                "Pesanan baru untuk listing '{$listing->title}' telah dibuat oleh @{$buyer->username}. Menunggu pembayaran."
            );

            $this->notificationService->notify(
                $buyer->user_id,
                $transaction->transaction_id,
                'TRANSACTION_CREATED',
                "Transaksi #{$transaction->transaction_id} berhasil dibuat. Selesaikan pembayaran dalam 1 jam."
            );
        }

        return $transaction;
    }
}
