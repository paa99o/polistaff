<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ic_number' => ['required', 'string', 'max:30', Rule::unique('users', 'ic_number')->ignore($this->user())],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'department' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
