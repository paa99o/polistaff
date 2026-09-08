<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'theme_preference' => ['required', Rule::in(['light', 'dark'])],
            'text_size_preference' => ['required', Rule::in(['normal', 'large'])],
            'reduce_motion' => ['sometimes', 'boolean'],
            'email_announcements' => ['sometimes', 'boolean'],
            'email_activities' => ['sometimes', 'boolean'],
            'email_finance' => ['sometimes', 'boolean'],
            'email_fee_reminders' => ['sometimes', 'boolean'],
        ];
    }
}
