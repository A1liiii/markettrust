<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'content' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required_without' => 'Isi pesan tidak boleh kosong jika tidak melampirkan file.',
            'content.max' => 'Pesan maksimal 5.000 karakter.',
            'attachment.file' => 'Berkas lampiran harus berupa file yang valid.',
            'attachment.max' => 'Ukuran file lampiran maksimal 5 MB.',
            'attachment.mimes' => 'Format file lampiran harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF.',
        ];
    }
}
