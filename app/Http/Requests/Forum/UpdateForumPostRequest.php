<?php

namespace App\Http\Requests\Forum;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateForumPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        return auth()->check() && $post && auth()->id() === $post->author_id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('category_code') === 'GENERAL') {
            $this->merge([
                'variant_id' => null,
                'price_offer' => null,
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
            'category_code' => ['required', 'in:GENERAL,WTB,WTS'],
            'variant_id' => [
                'nullable',
                'required_if:category_code,WTB,WTS',
                'exists:product_variants,variant_id',
            ],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'price_offer' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'keep_images' => ['nullable', 'array'],
            'keep_images.*' => ['string'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:3072'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $existingCount = is_array($this->input('keep_images')) ? count($this->input('keep_images')) : 0;
            $newCount = is_array($this->file('images')) ? count($this->file('images')) : 0;

            if (($existingCount + $newCount) > 4) {
                $validator->errors()->add('images', 'Total foto gabungan tidak boleh melebihi 4 foto.');
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_code.required' => 'Kategori postingan wajib dipilih.',
            'category_code.in' => 'Kategori postingan tidak valid.',
            'variant_id.required_if' => 'Varian produk wajib dipilih untuk kategori WTB atau WTS.',
            'variant_id.exists' => 'Varian produk yang dipilih tidak ditemukan.',
            'title.required' => 'Judul postingan wajib diisi.',
            'title.max' => 'Judul postingan maksimal 150 karakter.',
            'content.required' => 'Konten postingan wajib diisi.',
            'content.max' => 'Konten postingan maksimal 5000 karakter.',
            'price_offer.numeric' => 'Harga penawaran harus berupa angka nominal.',
            'price_offer.min' => 'Harga penawaran tidak boleh bernilai negatif.',
            'images.max' => 'Maksimal foto yang dapat diunggah adalah 4 foto.',
            'images.*.image' => 'File yang diunggah harus berupa gambar.',
            'images.*.mimes' => 'Format foto harus berupa JPG, PNG, atau WEBP.',
            'images.*.max' => 'Ukuran setiap foto maksimal 3 MB.',
        ];
    }
}
