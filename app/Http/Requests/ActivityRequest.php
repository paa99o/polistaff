<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_time' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'attendance_opens_at' => ['nullable', 'date'],
            'attendance_closes_at' => ['nullable', 'date', 'after_or_equal:attendance_opens_at'],
            'status' => ['required', 'in:draft,pending_approval,approved,cancelled'],
        ];
    }
}
