<?php

namespace App\Contracts\Services;

use App\DTOs\FeeCalculationResultDTO;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Models\Agreement;

interface FeeCalculationServiceInterface
{
    /**
     * Hitung rincian kalkulasi fee platform berdasarkan aturan bertingkat SKPL (Revisi Oktober 2026).
     *
     * @throws \InvalidArgumentException jika harga dasar di bawah minimum Rp5.000 atau mode fee tidak valid.
     */
    public function calculate(float $basePrice, FeeType|string $feeType = 'EXCLUDE'): FeeCalculationResultDTO;

    /**
     * Helper khusus jalur Marketplace:
     * Selalu membebankan fee ke Buyer (EXCLUDE).
     */
    public function calculateForMarketplace(float $listingPrice): FeeCalculationResultDTO;

    /**
     * Hitung nominal fee murni berdasarkan 7 jenjang SKPL dengan pembulatan ke atas (ceil).
     */
    public function calculateNominalFee(float $price): float;

    /**
     * Terapkan dan kunci snapshot finansial fee ke dalam model Agreement.
     * Snapshot pada agreement yang sudah difinalisasi tidak dihitung ulang dalam kondisi apa pun.
     *
     * @param  TransactionSourceType|string|null  $source  Sumber transaksi (MARKETPLACE atau FORUM). Jika null, diselesaikan dari relasi model tepercaya.
     *
     * @throws \LogicException jika agreement sudah FINALIZED tetapi snapshot finansialnya tidak lengkap.
     * @throws \InvalidArgumentException jika sumber transaksi tidak dapat ditentukan atau harga < Rp5.000.
     */
    public function applyToAgreement(Agreement $agreement, TransactionSourceType|string|null $source = null): Agreement;
}
