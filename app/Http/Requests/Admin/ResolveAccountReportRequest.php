<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResolveAccountReportRequest extends FormRequest
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
            'account_status' => ['nullable', 'string', 'in:ACTIVE,SUSPENDED'],
            'new_reputation_status' => ['nullable', 'string', 'in:LEGIT,PROBLEMATIC'],
            'points_delta' => ['nullable', 'integer', 'between:-1000,1000'],
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
            'review_note.required' => 'Catatan keputusan moderasi wajib diisi.',
            'review_note.min' => 'Catatan keputusan moderasi minimal 5 karakter.',
            'review_note.max' => 'Catatan keputusan moderasi maksimal 2000 karakter.',
            'account_status.in' => 'Pilihan status akun hanya ACTIVE atau SUSPENDED.',
            'new_reputation_status.in' => 'Pilihan status reputasi hanya LEGIT atau PROBLEMATIC.',
            'points_delta.integer' => 'Nominal perubahan poin reputasi harus berupa angka bulat.',
            'points_delta.between' => 'Nominal perubahan poin reputasi berkisar antara -1000 hingga 1000.',
        ];
    }
}
