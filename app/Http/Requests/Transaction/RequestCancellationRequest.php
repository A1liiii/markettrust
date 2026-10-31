<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;

class RequestCancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');
        $statusStr = $transaction?->status instanceof TransactionStatus
            ? $transaction->status->value
            : (string) ($transaction?->status ?? '');

        // Pada tahap PAID, alasan wajib diisi
        if ($statusStr === 'PAID') {
            return [
                'reason' => ['required', 'string', 'min:5', 'max:1000'],
            ];
        }

        // Pada tahap WAITING_PAYMENT, alasan opsional
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan wajib diisi untuk transaksi yang telah dibayar.',
            'reason.min' => 'Alasan pembatalan minimal harus 5 karakter.',
            'reason.max' => 'Alasan pembatalan maksimal 1000 karakter.',
        ];
    }
}
