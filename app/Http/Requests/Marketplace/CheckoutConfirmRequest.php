<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutConfirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isActive();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'string', 'max:100'],
            'expected_price' => ['required', 'numeric', 'min:5000'],
        ];
    }

    /**
     * Pesan validasi khusus konfirmasi checkout.
     */
    public function messages(): array
    {
        return [
            'submission_token.required' => 'Token transaksi tidak ditemukan. Muat ulang halaman checkout.',
            'expected_price.required' => 'Konfirmasi harga transaksi wajib disertakan.',
        ];
    }
}
