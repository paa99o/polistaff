<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ic_number' => ['required', 'string', 'max:30', 'unique:users,ic_number'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'department' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }
}
