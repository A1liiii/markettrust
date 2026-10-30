<?php

namespace App\Contracts\Services;

use App\Models\Listing;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MarketplaceServiceInterface
{
    /**
     * Cari listing aktif dengan filter bertingkat kategori-produk-varian dan pencarian teks.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchListings(array $filters, int $perPage = 12): LengthAwarePaginator;

    /**
     * Hitung statistik informasi pasar produk (UC05) untuk varian tertentu:
     * - Demand: Jumlah postingan WTB aktif
     * - Supply: Jumlah listing aktif + postingan WTS aktif
     * - Average Price: Rata-rata base_price transaksi selesai (COMPLETED) pada periode waktu tertentu
     * - Sample Count: Jumlah transaksi selesai yang menjadi sampel
     * - Has Price Data: Status apakah data harga tersedia
     * - Period: Periode waktu yang digunakan (7d, 30d, 90d, all)
     * - Period Label: Label teks periode ramah pengguna
     * - Period Days: Jumlah hari pada periode atau null jika all
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
    public function getProductMarketStats(int $variantId, ?string $period = '30d'): array;

    /**
     * Periksa apakah seller memiliki tujuan payout aktif (bank, e-wallet, atau QRIS)
     * sebelum checkout disetujui, sesuai ketentuan TC-09-06.
     */
    public function validateSellerPayoutAvailable(User $seller): bool;

    /**
     * Eksekusi alur checkout marketplace (UC09):
     * Membentuk Agreement FINALIZED, Transaction WAITING_PAYMENT bertenggat 1 jam,
     * pencatatan history status, dan pengiriman notifikasi.
     *
     * @throws \InvalidArgumentException jika listing tidak aktif, self-buy, atau harga berubah.
     * @throws \LogicException jika seller belum memiliki rekening payout.
     */
    public function checkout(User $buyer, Listing $listing, string $submissionToken, float $expectedPrice): Transaction;
}
