<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // user yang login boleh mengubah profilnya
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'   => ['required','string','max:255'],

            // pastikan unik tapi abaikan email milik user saat ini
            'email'  => [
                'required','string','email','max:255','lowercase',
                Rule::unique('users','email')->ignore($this->user()->id),
            ],

            // opsional, harus dikonfirmasi: field "password_confirmation"
            'password' => ['nullable','confirmed','min:6'],

            // upload foto (opsional)
            'photo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],

            // centang untuk hapus
            'remove_photo' => ['nullable','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->email)) {
            $this->merge(['email' => mb_strtolower($this->email)]);
        }
    }
}
