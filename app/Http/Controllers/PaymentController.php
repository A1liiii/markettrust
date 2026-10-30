<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PaymentServiceInterface;
use App\Models\Transaction;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentServiceInterface $paymentService
    ) {}

    /**
     * Generate or retrieve Midtrans Snap token for a transaction.
     */
    public function createSnapToken(Request $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->paymentService->getOrCreateSnapToken($transaction, $user);

            return response()->json([
                'snap_token' => $result['token'],
                'redirect_url' => $result['redirect_url'],
                'order_id' => $result['order_id'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan sistem saat memproses gateway pembayaran.',
            ], 500);
        }
    }

    /**
     * Handle incoming HTTP notification callback from Midtrans.
     */
    public function handleNotification(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $result = $this->paymentService->handleNotification($payload);

            return response()->json([
                'status' => $result['status'],
                'message' => $result['message'],
            ], 200);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Internal server error processing Midtrans notification.',
            ], 500);
        }
    }

    /**
     * Manually or automatically sync/reconcile payment status and deadline from Midtrans API.
     */
    public function syncStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();
        $agreement = $transaction->agreement;

        if (! $agreement || (! $user->isAdmin() && (int) $user->user_id !== (int) $agreement->buyer_id && (int) $user->user_id !== (int) $agreement->seller_id)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Anda tidak memiliki hak akses untuk memeriksa status transaksi ini.',
            ], 403);
        }

        try {
            $result = $this->paymentService->syncPaymentStatus($transaction);

            return response()->json($result);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
