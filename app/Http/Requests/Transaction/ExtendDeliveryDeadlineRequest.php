<?php

namespace App\Http\Requests\Transaction;

use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExtendDeliveryDeadlineRequest extends FormRequest
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
            && ($user->isAdmin() || ($transaction->agreement && $transaction->agreement->seller_id === $user->user_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'extension_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
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
            'extension_minutes.required' => 'Durasi perpanjangan waktu wajib ditentukan.',
            'extension_minutes.min' => 'Perpanjangan waktu minimal 15 menit.',
            'extension_minutes.max' => 'Perpanjangan waktu maksimal 1440 menit (24 jam).',
            'reason.required' => 'Alasan perpanjangan tenggat wajib diisi.',
            'reason.min' => 'Alasan perpanjangan minimal berisi 5 karakter.',
        ];
    }
}
