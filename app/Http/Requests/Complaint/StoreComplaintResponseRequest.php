<?php

namespace App\Http\Requests\Complaint;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        return $user !== null
            && $complaint !== null
            && $complaint->transaction !== null
            && $complaint->transaction->agreement !== null
            && (int) $complaint->transaction->agreement->seller_id === (int) $user->user_id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'response_text' => ['required', 'string', 'min:5', 'max:2000'],
            'evidence_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'response_text.required' => 'Tanggapan atau klarifikasi komplain wajib diisi.',
            'response_text.min' => 'Tanggapan minimal berisi 5 karakter.',
            'response_text.max' => 'Tanggapan maksimal berisi 2000 karakter.',
            'evidence_file.mimes' => 'Format berkas bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'evidence_file.max' => 'Ukuran berkas bukti maksimal adalah 5 MB.',
        ];
    }
}

