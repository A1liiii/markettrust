<?php

namespace App\Http\Requests\Transaction;

use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Transaction|null $transaction */
        $transaction = $this->route('transaction');

        return $user !== null
            && $transaction !== null
            && $transaction->agreement !== null
            && $transaction->agreement->seller_id === $user->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_note' => ['required', 'string', 'min:3', 'max:2000'],
            'result_link' => ['nullable', 'url', 'max:500'],
            'evidence_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivery_note.required' => 'Catatan penyerahan hasil pesanan wajib diisi.',
            'delivery_note.min' => 'Catatan penyerahan minimal berisi 3 karakter.',
            'delivery_note.max' => 'Catatan penyerahan maksimal 2000 karakter.',
            'result_link.url' => 'Format tautan hasil tidak valid (harus diawali http:// atau https://).',
            'result_link.max' => 'Tautan hasil maksimal 500 karakter.',
            'evidence_file.file' => 'Bukti resmi yang diunggah harus berupa berkas file valid.',
            'evidence_file.mimes' => 'Format bukti resmi hanya diperbolehkan gambar (JPG, JPEG, PNG, WEBP) atau PDF.',
            'evidence_file.max' => 'Ukuran berkas bukti resmi maksimal 5 MB (5120 KB).',
        ];
    }
}
