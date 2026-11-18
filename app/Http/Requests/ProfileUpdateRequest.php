<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('tel')) {
            $this->merge(['tel' => preg_replace('/[^\d+]/', '', (string) $this->input('tel'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['tel.regex' => 'Numéro invalide. Exemple : 0700000000 ou +2250700000000.'];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'regex:/^\+?\d{8,15}$/'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
