<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarketplaceSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,category_id'],
            'product_id' => ['nullable', 'integer', 'exists:products,product_id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,variant_id'],
            'sort' => ['nullable', 'string', 'in:latest,price_asc,price_desc'],
        ];
    }
}
