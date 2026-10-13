<?php

namespace App\Http\Requests\Seller;

use App\Models\Listing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        if (! $listing instanceof Listing) {
            $listing = Listing::find($listing);
        }

        return $listing !== null && $this->user()?->can('update', $listing);
    }

    /**
     * Persiapkan dan bersihkan input sebelum validasi dijalankan.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('price')) {
            $priceVal = $this->input('price');
            if (is_numeric($priceVal)) {
                $this->merge(['price' => (float) $priceVal]);

                return;
            }

            if (is_string($priceVal)) {
                $cleaned = trim(str_ireplace(['rp', ' '], '', $priceVal));
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
                if (is_numeric($cleaned)) {
                    $this->merge(['price' => (float) $cleaned]);
                }
            }
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
            'variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'variant_id')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:5000', 'max:999999999'],
            'delivery_method' => ['required', 'string', 'max:100'],
            'estimated_delivery_value' => ['required', 'integer', 'min:1', 'max:999'],
            'estimated_delivery_unit' => ['required', 'in:MINUTE,HOUR,DAY'],
            'status' => ['required', 'in:DRAFT,ACTIVE,CLOSED'],
        ];
    }

    /**
     * Pesan kustom untuk validasi pembaruan listing seller.
     */
    public function messages(): array
    {
        return [
            'variant_id.required' => 'Varian produk master wajib dipilih.',
            'variant_id.exists' => 'Varian produk yang dipilih tidak valid atau sedang dinonaktifkan.',
            'title.required' => 'Judul penawaran listing wajib diisi.',
            'title.max' => 'Judul penawaran maksimal 150 karakter.',
            'description.required' => 'Deskripsi spesifikasi item digital wajib diisi.',
            'price.required' => 'Harga penawaran bersih wajib diisi.',
            'price.numeric' => 'Harga penawaran harus berupa nominal angka yang valid.',
            'price.min' => 'Harga penawaran minimal adalah Rp 5.000 sesuai batas minimum transaksi platform.',
            'delivery_method.required' => 'Metode penyerahan digital wajib diisi.',
            'estimated_delivery_value.required' => 'Estimasi durasi penyerahan wajib diisi.',
            'estimated_delivery_value.min' => 'Estimasi durasi penyerahan harus bernilai minimal 1.',
            'estimated_delivery_unit.required' => 'Satuan estimasi durasi wajib dipilih.',
            'estimated_delivery_unit.in' => 'Satuan estimasi harus berupa MINUTE (Menit), HOUR (Jam), atau DAY (Hari).',
            'status.required' => 'Status penawaran wajib ditentukan.',
            'status.in' => 'Status penawaran harus berupa DRAFT, ACTIVE, atau CLOSED.',
        ];
    }
}
