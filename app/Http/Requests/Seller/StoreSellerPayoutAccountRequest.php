<?php

namespace App\Http\Requests\Seller;

use App\Enums\PayoutDestinationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSellerPayoutAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isActive();
    }

    /**
     * Persiapkan dan bersihkan input sebelum validasi dijalankan.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('destination_type')) {
            $this->merge([
                'destination_type' => strtoupper(trim((string) $this->input('destination_type'))),
            ]);
        }

        if ($this->has('destination_provider')) {
            $this->merge([
                'destination_provider' => trim((string) $this->input('destination_provider')),
            ]);
        }

        if ($this->has('destination_number')) {
            $this->merge([
                'destination_number' => trim((string) $this->input('destination_number')),
            ]);
        }

        if ($this->has('destination_holder')) {
            $this->merge([
                'destination_holder' => trim((string) $this->input('destination_holder')),
            ]);
        }

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
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
        $isBankOrEwallet = in_array($this->input('destination_type'), ['BANK', 'EWALLET', PayoutDestinationType::BANK->value, PayoutDestinationType::EWALLET->value], true);
        $isQris = in_array($this->input('destination_type'), ['QRIS', PayoutDestinationType::QRIS->value], true);

        return [
            'destination_type' => ['required', Rule::enum(PayoutDestinationType::class)],
            'destination_holder' => ['required', 'string', 'min:3', 'max:100'],
            'destination_provider' => [
                Rule::requiredIf($isBankOrEwallet),
                'nullable',
                'string',
                'max:30',
            ],
            'destination_number' => [
                Rule::requiredIf($isBankOrEwallet),
                'nullable',
                'string',
                'max:50',
            ],
            'qris_image' => [
                Rule::requiredIf($isQris),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Pesan kustom untuk kegagalan validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination_type.required' => 'Tipe tujuan pencairan dana wajib dipilih.',
            'destination_type.enum' => 'Tipe tujuan pencairan dana harus berupa BANK, EWALLET, atau QRIS.',
            'destination_holder.required' => 'Nama pemilik rekening atau akun e-wallet/QRIS wajib diisi.',
            'destination_holder.min' => 'Nama pemilik minimal 3 karakter.',
            'destination_holder.max' => 'Nama pemilik maksimal 100 karakter.',
            'destination_provider.required' => 'Nama bank atau penyedia e-wallet wajib diisi.',
            'destination_provider.max' => 'Nama bank/penyedia maksimal 30 karakter.',
            'destination_number.required' => 'Nomor rekening atau nomor ponsel tujuan wajib diisi.',
            'destination_number.max' => 'Nomor tujuan maksimal 50 karakter.',
            'qris_image.required' => 'File gambar barcode QRIS wajib diunggah untuk tipe tujuan QRIS.',
            'qris_image.image' => 'File gambar QRIS harus berupa gambar yang valid.',
            'qris_image.mimes' => 'Format gambar QRIS harus berupa JPG, JPEG, PNG, atau WEBP.',
            'qris_image.max' => 'Ukuran gambar QRIS maksimal 2MB.',
        ];
    }
}
