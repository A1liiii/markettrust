<?php

namespace App\Http\Requests\AccountReport;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateAccountReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $this->merge([
                'reason' => trim((string) $this->input('reason')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reported_user_id' => ['required', 'integer', 'exists:users,user_id'],
            'transaction_id' => ['nullable', 'integer', 'exists:transactions,transaction_id'],
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * Custom message for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reported_user_id.required' => 'Pengguna yang dilaporkan wajib dipilih.',
            'reported_user_id.exists' => 'Akun pengguna yang dilaporkan tidak ditemukan.',
            'transaction_id.exists' => 'Transaksi rujukan tidak valid atau tidak ditemukan.',
            'reason.required' => 'Alasan pelaporan wajib diisi.',
            'reason.min' => 'Alasan pelaporan minimal 10 karakter untuk menjelaskan duduk perkara.',
            'reason.max' => 'Alasan pelaporan maksimal 3000 karakter.',
            'evidence.mimes' => 'Berkas bukti pendukung harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF.',
            'evidence.max' => 'Ukuran berkas bukti maksimal 5 MB (5120 KB).',
        ];
    }
}
