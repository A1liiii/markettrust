<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RequestDisputeEvidenceRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'extension_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan permintaan bukti atau klarifikasi tambahan wajib diisi.',
            'reason.min' => 'Alasan minimal berisi 5 karakter.',
            'reason.max' => 'Alasan maksimal berisi 1000 karakter.',
            'extension_hours.min' => 'Perpanjangan waktu minimal 1 jam.',
            'extension_hours.max' => 'Perpanjangan waktu maksimal 72 jam.',
        ];
    }
}

