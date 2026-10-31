<?php

namespace App\Http\Requests\Complaint;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Transaction|null $transaction */
        $transaction = $this->route('transaction');

        return $user !== null
            && $transaction !== null
            && $transaction->agreement !== null
            && (int) $transaction->agreement->buyer_id === (int) $user->user_id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence_file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan komplain wajib diisi secara rinci.',
            'reason.min' => 'Alasan komplain minimal berisi 10 karakter.',
            'reason.max' => 'Alasan komplain maksimal berisi 2000 karakter.',
            'evidence_file.required' => 'Berkas bukti resmi komplain wajib diunggah.',
            'evidence_file.file' => 'Berkas bukti harus berupa file yang valid.',
            'evidence_file.mimes' => 'Format berkas bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'evidence_file.max' => 'Ukuran berkas bukti maksimal adalah 5 MB.',
        ];
    }
}

