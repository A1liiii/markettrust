<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DecideDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:PAYOUT,REFUND'],
            'decision_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan sengketa wajib dipilih.',
            'decision.in' => 'Keputusan sengketa harus berupa PAYOUT atau REFUND.',
            'decision_reason.required' => 'Alasan keputusan sengketa wajib diisi.',
            'decision_reason.min' => 'Alasan keputusan minimal berisi 10 karakter.',
            'decision_reason.max' => 'Alasan keputusan maksimal berisi 2000 karakter.',
        ];
    }
}

