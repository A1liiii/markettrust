<?php

namespace App\Http\Requests\Agreement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProposeAgreementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:BUYER,SELLER,buyer,seller'],
            'variant_id' => ['required', 'integer', 'exists:product_variants,variant_id'],
            'item_title' => ['required', 'string', 'min:3', 'max:150'],
            'item_description' => ['required', 'string', 'min:5', 'max:2000'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'base_price' => ['required', 'numeric', 'min:5000'],
            'fee_type' => ['required', 'string', 'in:EXCLUDE,INCLUDE,exclude,include'],
            'delivery_method' => ['required', 'string', 'in:INSTANT,DIRECT_CHAT'],
            'delivery_within_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'agreement_note' => ['nullable', 'string', 'max:1000'],
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
            'role.required' => 'Pilihan peran sebagai Pembeli atau Penjual wajib ditentukan.',
            'role.in' => 'Peran tidak valid. Pilih Pembeli atau Penjual.',
            'variant_id.required' => 'Varian produk digital wajib dipilih.',
            'variant_id.exists' => 'Varian produk digital tidak ditemukan dalam katalog.',
            'item_title.required' => 'Judul item kesepakatan wajib diisi.',
            'item_title.min' => 'Judul item minimal terdiri dari 3 karakter.',
            'item_title.max' => 'Judul item maksimal 150 karakter.',
            'item_description.required' => 'Rincian spesifikasi yang dijanjikan wajib diisi.',
            'item_description.min' => 'Rincian spesifikasi minimal 5 karakter.',
            'base_price.required' => 'Harga dasar kesepakatan wajib diisi.',
            'base_price.numeric' => 'Harga dasar harus berupa nominal angka valid.',
            'base_price.min' => 'Harga dasar transaksi di bawah batas minimum yang diizinkan (Rp5.000).',
            'fee_type.required' => 'Mode pembebanan fee (EXCLUDE/INCLUDE) wajib dipilih.',
            'fee_type.in' => 'Mode fee hanya menerima EXCLUDE atau INCLUDE.',
            'delivery_method.required' => 'Metode penyerahan wajib dipilih.',
            'delivery_method.in' => 'Metode penyerahan harus bernilai INSTANT atau DIRECT_CHAT.',
            'delivery_within_minutes.required' => 'Estimasi waktu penyerahan wajib ditentukan.',
            'delivery_within_minutes.min' => 'Estimasi waktu penyerahan minimal 1 menit.',
        ];
    }
}
