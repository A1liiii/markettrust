<?php

namespace App\Services;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Models\Agreement;
use InvalidArgumentException;
use LogicException;

class FeeCalculationService implements FeeCalculationServiceInterface
{
    /**
     * Hitung rincian fee platform berdasarkan tabel rumus bertingkat SKPL (Revisi Oktober 2026).
     * Fee dihitung murni dari aturan kode aplikasi dan hasilnya dikunci sebagai snapshot.
     *
     * @throws InvalidArgumentException
     */
    public function calculate(float $basePrice, FeeType|string $feeType = 'EXCLUDE'): FeeCalculationResultDTO
    {
        if ($feeType instanceof FeeType) {
            $feeTypeEnum = $feeType;
        } else {
            $normalizedStr = strtoupper(trim((string) $feeType));
            if (! in_array($normalizedStr, ['EXCLUDE', 'INCLUDE'], true)) {
                throw new InvalidArgumentException('Mode fee tidak valid. Hanya menerima EXCLUDE atau INCLUDE.');
            }
            $feeTypeEnum = FeeType::from($normalizedStr);
        }

        if ($basePrice < 5000) {
            throw new InvalidArgumentException(sprintf(
                'Harga dasar transaksi (Rp%s) di bawah batas minimum yang diizinkan (Rp5.000).',
                number_format($basePrice, 0, ',', '.')
            ));
        }

        $feeAmount = $this->calculateNominalFee($basePrice);

        // Aturan Pembebanan Fee:
        // EXCLUDE: Buyer membayar harga dasar + fee; Seller menerima harga dasar penuh.
        // INCLUDE: Buyer membayar harga dasar; Seller menerima harga dasar dipotong fee.
        if ($feeTypeEnum === FeeType::EXCLUDE) {
            $buyerTotal = $basePrice + $feeAmount;
            $sellerNet = $basePrice;
        } else {
            $buyerTotal = $basePrice;
            $sellerNet = max(0.0, $basePrice - $feeAmount);
        }

        return new FeeCalculationResultDTO(
            basePrice: $basePrice,
            feeType: $feeTypeEnum,
            feeAmount: (float) $feeAmount,
            buyerTotal: (float) $buyerTotal,
            sellerNet: (float) $sellerNet
        );
    }

    /**
     * Helper khusus jalur Marketplace:
     * Fee layanan wajib dihitung dari harga listing dan ditambahkan pada jumlah pembayaran Buyer.
     * Seller menerima harga listing sebagai haknya. Selalu dicatat sebagai EXCLUDE.
     *
     * @throws InvalidArgumentException
     */
    public function calculateForMarketplace(float $listingPrice): FeeCalculationResultDTO
    {
        return $this->calculate($listingPrice, FeeType::EXCLUDE);
    }

    /**
     * Hitung nominal fee murni berdasarkan 7 jenjang SKPL dengan pembulatan ke atas (ceil):
     * 1. Rp5.000 - Rp10.000: 10% (flat) -> Rp500 - Rp1.000
     * 2. Rp10.001 - Rp50.000: 10% -> 5% (turun) -> Rp1.000 - Rp2.500
     * 3. Rp50.001 - Rp100.000: 5% (flat) -> Rp2.500 - Rp5.000
     * 4. Rp100.001 - Rp499.000: 5% -> 1,4% (turun) -> Rp5.000 - Rp7.000
     * 5. Rp499.001 - Rp1.000.000: 1,4% -> 1% (turun) -> Rp7.000 - Rp10.000
     * 6. > Rp1.000.000 s.d. < Rp10.000.000: Rp10.000 (flat)
     * 7. >= Rp10.000.000: Rp20.000 pada Rp10jt, bertambah Rp10.000 per kelipatan Rp10jt berikutnya.
     *
     * @throws InvalidArgumentException
     */
    public function calculateNominalFee(float $price): float
    {
        if ($price < 5000) {
            throw new InvalidArgumentException(sprintf(
                'Harga dasar transaksi (Rp%s) di bawah batas minimum yang diizinkan (Rp5.000).',
                number_format($price, 0, ',', '.')
            ));
        }

        $rawFee = match (true) {
            $price <= 10000 => $price * 0.10,
            $price <= 50000 => 1000 + (($price - 10000) / 40000) * 1500,
            $price <= 100000 => $price * 0.05,
            $price <= 499000 => 5000 + (($price - 100000) / 399000) * 2000,
            $price <= 1000000 => 7000 + (($price - 499000) / 501000) * 3000,
            $price < 10000000 => 10000.0,
            default => 20000.0 + (floor(($price - 10000000) / 10000000) * 10000.0),
        };

        return (float) ceil($rawFee);
    }

    /**
     * Terapkan dan kunci snapshot finansial fee ke dalam model Agreement.
     *
     * 1. Agreement berstatus FINALIZED atau memiliki finalized_at TIDAK BOLEH dihitung ulang
     *    dalam kondisi apa pun. Jika snapshot finansialnya tidak lengkap, lempar LogicException.
     * 2. Sumber transaksi ditentukan dari parameter tepercaya ($source) atau relasi model
     *    (conversation->listing_id atau transaction->source_type).
     * 3. Jika sumber adalah MARKETPLACE, server-side SELALU memaksa EXCLUDE.
     *
     * @throws LogicException jika agreement FINALIZED tetapi snapshot finansial tidak lengkap.
     * @throws InvalidArgumentException jika sumber transaksi tidak dapat ditentukan atau harga < Rp5.000.
     */
    public function applyToAgreement(Agreement $agreement, TransactionSourceType|string|null $source = null): Agreement
    {
        // Snapshot Immutability: jika agreement sudah FINALIZED, jangan hitung ulang dalam kondisi apa pun
        $isFinalized = ($agreement->status instanceof AgreementStatus && $agreement->status === AgreementStatus::FINALIZED)
            || $agreement->status === 'FINALIZED'
            || ! empty($agreement->finalized_at);

        if ($isFinalized) {
            $hasCompleteSnapshot = ! is_null($agreement->fee_amount)
                && ! is_null($agreement->buyer_total)
                && ! is_null($agreement->seller_net)
                && ! is_null($agreement->base_price)
                && ! is_null($agreement->fee_type);

            if (! $hasCompleteSnapshot) {
                throw new LogicException('Kesepakatan sudah berstatus FINALIZED tetapi snapshot finansial tidak lengkap.');
            }

            return $agreement;
        }

        // 1. Deteksi sumber tepercaya dari relasi yang tersimpan / terhubung
        $relationSource = null;

        if ($agreement->relationLoaded('transaction') && $agreement->transaction?->source_type) {
            $relationSource = $agreement->transaction->source_type instanceof TransactionSourceType
                ? $agreement->transaction->source_type->value
                : (string) $agreement->transaction->source_type;
        } elseif ($agreement->relationLoaded('conversation') && ! empty($agreement->conversation?->listing_id)) {
            $relationSource = TransactionSourceType::MARKETPLACE->value;
        } elseif ($agreement->relationLoaded('conversation') && ! empty($agreement->conversation?->post_id)) {
            $relationSource = TransactionSourceType::FORUM->value;
        } elseif ($agreement->exists) {
            if ($agreement->transaction()->whereNotNull('source_type')->exists()) {
                $relationSource = (string) $agreement->transaction()->value('source_type');
            } elseif ($agreement->conversation()->whereNotNull('listing_id')->exists()) {
                $relationSource = TransactionSourceType::MARKETPLACE->value;
            } elseif ($agreement->conversation()->whereNotNull('post_id')->exists()) {
                $relationSource = TransactionSourceType::FORUM->value;
            }
        }

        // 2. Normalisasi sumber eksplisit jika diberikan
        $explicitSource = null;
        if ($source !== null) {
            $explicitSource = $source instanceof TransactionSourceType
                ? $source->value
                : strtoupper(trim((string) $source));

            if (! in_array($explicitSource, [TransactionSourceType::MARKETPLACE->value, TransactionSourceType::FORUM->value], true)) {
                throw new InvalidArgumentException("Sumber transaksi tidak valid: '{$explicitSource}'. Hanya menerima 'MARKETPLACE' atau 'FORUM'.");
            }
        }

        // 3. Rekonsiliasi sumber: Sumber eksplisit TIDAK BISA mengalahkan sumber relasi tersimpan
        if ($relationSource !== null) {
            if ($explicitSource !== null && $explicitSource !== $relationSource) {
                throw new InvalidArgumentException(sprintf(
                    "Sumber transaksi eksplisit ('%s') bertentangan dengan sumber relasi tersimpan ('%s'). Transaksi terkait listing marketplace tidak dapat diproses sebagai FORUM.",
                    $explicitSource,
                    $relationSource
                ));
            }

            $resolvedSource = $relationSource;
        } else {
            // Untuk agreement baru yang belum memiliki relasi tersimpan, gunakan sumber eksplisit dari alur server internal
            if ($explicitSource === null) {
                throw new InvalidArgumentException("Sumber transaksi wajib ditentukan ('MARKETPLACE' atau 'FORUM') untuk menerapkan fee pada agreement.");
            }

            $resolvedSource = $explicitSource;
        }

        // Keputusan Bisnis: Jalur marketplace wajib EXCLUDE (Buyer menanggung fee, Seller terima listing penuh)
        $feeType = ($resolvedSource === TransactionSourceType::MARKETPLACE->value)
            ? FeeType::EXCLUDE
            : ($agreement->fee_type ?? FeeType::EXCLUDE);

        $result = $this->calculate(
            basePrice: (float) $agreement->base_price,
            feeType: $feeType
        );

        $agreement->fee_type = $result->feeType;
        $agreement->fee_amount = $result->feeAmount;
        $agreement->buyer_total = $result->buyerTotal;
        $agreement->seller_net = $result->sellerNet;

        return $agreement;
    }
}
