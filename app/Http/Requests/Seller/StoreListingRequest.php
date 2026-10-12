<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
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
            'variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'variant_id')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'delivery_method' => ['required', 'string', 'max:100'],
            'estimated_delivery_value' => ['required', 'integer', 'min:1', 'max:999'],
            'estimated_delivery_unit' => ['required', 'in:MINUTE,HOUR,DAY'],
            'status' => ['required', 'in:DRAFT,ACTIVE,CLOSED'],
        ];
    }

    /**
     * Pesan kustom untuk validasi form penawaran listing seller.
     */
    public function messages(): array
    {
        return [
            'variant_id.exists' => 'Varian produk yang dipilih tidak valid atau sedang dinonaktifkan.',
            'price.min' => 'Harga penawaran minimal adalah Rp 1.000.',
            'estimated_delivery_value.min' => 'Estimasi durasi penyerahan harus bernilai minimal 1.',
            'estimated_delivery_unit.in' => 'Satuan estimasi harus berupa MINUTE (Menit), HOUR (Jam), atau DAY (Hari).',
            'status.in' => 'Status penawaran harus berupa DRAFT, ACTIVE, atau CLOSED.',
        ];
    }
}
