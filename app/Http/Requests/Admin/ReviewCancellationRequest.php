<?php

namespace App\Http\Requests\Admin;

use App\Models\TransactionCancellation;
use Illuminate\Foundation\Http\FormRequest;

class ReviewCancellationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:'.TransactionCancellation::STATUS_APPROVED.','.TransactionCancellation::STATUS_REJECTED],
            'responsible_party' => [
                'required_if:decision,'.TransactionCancellation::STATUS_APPROVED,
                'nullable',
                'string',
                'in:'.implode(',', [
                    TransactionCancellation::PARTY_BUYER,
                    TransactionCancellation::PARTY_SELLER,
                    TransactionCancellation::PARTY_PLATFORM,
                    TransactionCancellation::PARTY_UNDETERMINED,
                ]),
            ],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan peninjauan wajib dipilih.',
            'decision.in' => 'Keputusan harus berupa APPROVED atau REJECTED.',
            'responsible_party.required_if' => 'Pihak yang bertanggung jawab wajib ditentukan jika pembatalan disetujui.',
            'responsible_party.in' => 'Pihak yang bertanggung jawab tidak valid.',
            'decision_note.max' => 'Catatan keputusan maksimal 1000 karakter.',
        ];
    }
}

