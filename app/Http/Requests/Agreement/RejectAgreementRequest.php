<?php

namespace App\Http\Requests\Agreement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectAgreementRequest extends FormRequest
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
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
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
            'rejection_reason.required' => 'Alasan penolakan kesepakatan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal terdiri dari 3 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter.',
        ];
    }
}
