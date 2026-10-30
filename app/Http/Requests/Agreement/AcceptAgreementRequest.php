<?php

namespace App\Http\Requests\Agreement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AcceptAgreementRequest extends FormRequest
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
            'revision_no' => ['required', 'integer', 'min:1'],
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
            'revision_no.required' => 'Nomor revisi kesepakatan wajib disertakan.',
            'revision_no.integer' => 'Nomor revisi kesepakatan harus berupa angka integer.',
            'revision_no.min' => 'Nomor revisi kesepakatan tidak valid.',
        ];
    }
}
