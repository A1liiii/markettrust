<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeeConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'min_amount' => ['required', 'numeric', 'min:1000', 'lte:max_amount'],
            'max_amount' => ['required', 'numeric', 'gte:min_amount', 'max:100000000'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'flat_fee' => ['required', 'numeric', 'min:0'],
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
            'min_amount.required' => 'Batas minimum transaksi wajib diisi.',
            'min_amount.numeric' => 'Batas minimum transaksi harus berupa angka.',
            'min_amount.min' => 'Batas minimum transaksi minimal Rp1.000.',
            'min_amount.lte' => 'Batas minimum tidak boleh lebih besar dari batas maksimum.',
            'max_amount.required' => 'Batas maksimum transaksi wajib diisi.',
            'max_amount.numeric' => 'Batas maksimum transaksi harus berupa angka.',
            'max_amount.gte' => 'Batas maksimum harus lebih besar atau sama dengan batas minimum.',
            'fee_percent.required' => 'Persentase fee wajib diisi.',
            'fee_percent.numeric' => 'Persentase fee harus berupa angka.',
            'fee_percent.min' => 'Persentase fee minimal 0%.',
            'fee_percent.max' => 'Persentase fee maksimal 50%.',
            'flat_fee.required' => 'Batas bawah flat fee wajib diisi.',
            'flat_fee.numeric' => 'Batas bawah flat fee harus berupa angka.',
            'flat_fee.min' => 'Batas bawah flat fee minimal Rp0.',
        ];
    }
}
