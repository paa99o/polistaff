<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('activity_date') && $this->filled('start_time')) {
            $this->merge(['date_time' => $this->input('activity_date').' '.$this->input('start_time')]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date_time' => ['required', 'date'],
            'activity_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'location' => ['required', 'string', 'max:255'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at', 'before_or_equal:date_time'],
            'status' => ['required', 'in:draft,pending_approval,approved,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_closes_at.after_or_equal' => 'Registration Closes mesti sama atau selepas Registration Opens.',
            'registration_closes_at.before_or_equal' => 'Registration Closes mesti pada atau sebelum tarikh aktiviti.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tajuk',
            'date_time' => 'tarikh dan masa',
            'activity_date' => 'tarikh aktiviti',
            'start_time' => 'masa bermula',
            'end_time' => 'masa berakhir',
            'location' => 'lokasi',
            'max_participants' => 'maksimum peserta',
            'registration_opens_at' => 'registration opens',
            'registration_closes_at' => 'registration closes',
            'status' => 'status',
        ];
    }
}
