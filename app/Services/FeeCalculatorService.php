<?php

namespace App\Services;

use App\DTO\FeeCalculationResult;
use App\Models\Agreement;
use InvalidArgumentException;

class FeeCalculatorService
{
    /**
     * Hitung rincian fee platform berdasarkan tabel rumus bertingkat SKPL (Revisi Oktober 2026).
     * Fee dihitung murni dari aturan kode aplikasi (bukan membaca tabel fee_configs)
     * dan hasilnya dikunci sebagai agreement snapshot.
     *
     * @throws InvalidArgumentException
     */
    public function calculate(float $basePrice, string $feeType = 'EXCLUDE'): FeeCalculationResult
    {
        $feeType = strtoupper(trim($feeType));

        if (! in_array($feeType, ['EXCLUDE', 'INCLUDE'], true)) {
            throw new InvalidArgumentException('Mode fee tidak valid. Hanya menerima EXCLUDE atau INCLUDE.');
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
        if ($feeType === 'EXCLUDE') {
            $buyerTotal = $basePrice + $feeAmount;
            $sellerNet = $basePrice;
        } else {
            $buyerTotal = $basePrice;
            $sellerNet = max(0.0, $basePrice - $feeAmount);
        }

        $effectivePercent = round(($feeAmount / $basePrice) * 100, 2);

        return new FeeCalculationResult(
            basePrice: $basePrice,
            feeType: $feeType,
            feePercent: $effectivePercent,
            flatFee: ($basePrice > 1000000 ? 10000.0 : 0.0),
            feeAmount: (float) $feeAmount,
            buyerTotal: (float) $buyerTotal,
            sellerNet: (float) $sellerNet,
            feeConfigId: null,
        );
    }

    /**
     * Helper khusus jalur Marketplace:
     * Fee layanan wajib dihitung dari harga listing dan ditambahkan pada jumlah pembayaran Buyer.
     * Seller menerima harga listing sebagai haknya. Selalu dicatat sebagai EXCLUDE.
     */
    public function calculateForMarketplace(float $listingPrice): FeeCalculationResult
    {
        return $this->calculate($listingPrice, 'EXCLUDE');
    }

    /**
     * Hitung nominal fee murni berdasarkan 7 jenjang SKPL:
     * 1. Rp5.000 - Rp10.000: 10% (flat) -> Rp500 - Rp1.000
     * 2. Rp10.001 - Rp50.000: 10% -> 5% (turun) -> Rp1.000 - Rp2.500
     * 3. Rp50.001 - Rp100.000: 5% (flat) -> Rp2.500 - Rp5.000
     * 4. Rp100.001 - Rp499.000: 5% -> 1,4% (turun) -> Rp5.000 - Rp7.000
     * 5. Rp499.001 - Rp1.000.000: 1,4% -> 1% (turun) -> Rp7.000 - Rp10.000
     * 6. > Rp1.000.000 s.d. < Rp10.000.000: Rp10.000 (flat)
     * 7. >= Rp10.000.000: Rp20.000 pada Rp10jt, naik Rp10.000 per kelipatan Rp10jt berikutnya.
     */
    public function calculateNominalFee(float $price): float
    {
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
     * Kunci snapshot finansial fee ke dalam model Agreement secara independen tanpa query fee_configs.
     */
    public function applyToAgreement(Agreement $agreement): Agreement
    {
        $feeType = (string) ($agreement->fee_type ?? 'EXCLUDE');
        $result = $this->calculate(
            basePrice: (float) $agreement->base_price,
            feeType: $feeType
        );

        $agreement->fee_config_id = null;
        $agreement->fee_amount = $result->feeAmount;
        $agreement->buyer_total = $result->buyerTotal;
        $agreement->seller_net = $result->sellerNet;

        return $agreement;
    }
}
