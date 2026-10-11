<?php

namespace App\Http\Controllers;

use App\Services\FeeCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FeePreviewController extends Controller
{
    /**
     * Endpoint preview perhitungan fee untuk frontend / form transaksi.
     */
    public function preview(Request $request, FeeCalculatorService $feeService): JsonResponse
    {
        $validated = $request->validate([
            'base_price' => ['required', 'numeric'],
            'fee_type' => ['nullable', 'string', 'in:EXCLUDE,INCLUDE,exclude,include'],
        ], [
            'base_price.required' => 'Nominal harga dasar wajib diisi.',
            'base_price.numeric' => 'Nominal harga dasar harus berupa angka.',
            'fee_type.in' => 'Mode fee harus bernilai EXCLUDE atau INCLUDE.',
        ]);

        $feeType = strtoupper((string) ($validated['fee_type'] ?? 'EXCLUDE'));

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
