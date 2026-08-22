<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'cedula' => ['nullable', 'string', 'max:20', 'regex:/^[VE]-\d{4,9}$/', Rule::unique(User::class)->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]{7,20}$/'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]{7,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'cedula.regex' => 'La cédula debe tener el formato V-12345678 o E-12345678',
            'phone.regex' => 'El teléfono debe tener un formato válido',
            'emergency_contact_phone.regex' => 'El teléfono de emergencia debe tener un formato válido',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy',
        ];
    }
}
