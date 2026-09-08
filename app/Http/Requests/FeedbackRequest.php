<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'activity_id' => ['nullable', Rule::exists('activities', 'id')->where('status', 'approved')],
            'content' => ['required', 'string', 'max:3000'],
            'rating' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
