<?php

namespace App\Http\Controllers;

use App\Contracts\Services\FeeCalculationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FeePreviewController extends Controller
{
    /**
     * Endpoint preview perhitungan fee untuk frontend / form transaksi.
     */
    public function preview(Request $request, FeeCalculationServiceInterface $feeService): JsonResponse
    {
        $validated = $request->validate([
            'base_price' => ['required', 'numeric'],
            'fee_type' => ['nullable', 'string', 'in:EXCLUDE,INCLUDE,exclude,include'],
            'source' => ['nullable', 'string', 'in:MARKETPLACE,FORUM,marketplace,forum'],
        ], [
            'base_price.required' => 'Nominal harga dasar wajib diisi.',
            'base_price.numeric' => 'Nominal harga dasar harus berupa angka.',
            'fee_type.in' => 'Mode fee harus bernilai EXCLUDE atau INCLUDE.',
            'source.in' => 'Sumber transaksi tidak valid.',
        ]);

        $source = isset($validated['source']) ? strtoupper(trim((string) $validated['source'])) : null;

        // Keputusan Bisnis: Pada marketplace, paksa pembebanan EXCLUDE di sisi server
        // terlepas dari parameter fee_type yang dikirimkan klien.
        if ($source === 'MARKETPLACE') {
            $feeType = 'EXCLUDE';
        } else {
            $feeType = strtoupper((string) ($validated['fee_type'] ?? 'EXCLUDE'));
        }

        try {
            $result = $feeService->calculate((float) $validated['base_price'], $feeType);

            return response()->json([
                'status' => 'success',
                'data' => $result->toArray(),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
