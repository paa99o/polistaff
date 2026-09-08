<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'target' => ['required', Rule::in(['all_active', 'department', 'role', 'individual'])],
            'department' => ['nullable', 'required_if:target,department', 'string', 'max:255'],
            'role' => ['nullable', 'required_if:target,role', Rule::in(['member', 'treasurer', 'chairman', 'admin'])],
            'user_id' => ['nullable', 'required_if:target,individual', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'link' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required' => 'Sila pilih sasaran penerima notifikasi.',
            'department.required_if' => 'Sila pilih department untuk sasaran department.',
            'role.required_if' => 'Sila pilih role untuk sasaran role.',
            'user_id.required_if' => 'Sila pilih ahli untuk sasaran individu.',
        ];
    }

    public function attributes(): array
    {
        return [
            'target' => 'sasaran',
            'department' => 'department',
            'role' => 'role',
            'user_id' => 'ahli',
            'title' => 'tajuk',
            'message' => 'mesej',
            'type' => 'jenis',
            'link' => 'link',
        ];
    }
}
