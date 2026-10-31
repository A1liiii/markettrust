<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewAccountReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('review_note')) {
            $this->merge([
                'review_note' => trim((string) $this->input('review_note')),
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
            'review_note' => ['required', 'string', 'min:5', 'max:2000'],
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
            'review_note.required' => 'Catatan peninjauan administrator wajib diisi.',
            'review_note.min' => 'Catatan peninjauan minimal terdiri dari 5 karakter.',
            'review_note.max' => 'Catatan peninjauan maksimal 2000 karakter.',
        ];
    }
}
