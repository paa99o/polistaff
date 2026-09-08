<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;
use App\Models\Feedback;
use Illuminate\Validation\Validator;
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $activityId = $this->input('activity_id');

                if (! $activityId || $validator->errors()->has('activity_id')) {
                    return;
                }

                if (! Attendance::where('user_id', $this->user()->id)->where('activity_id', $activityId)->exists()) {
                    $validator->errors()->add('activity_id', 'Anda hanya boleh memberi maklum balas selepas hadir ke aktiviti tersebut.');
                }

                if (Feedback::where('user_id', $this->user()->id)->where('activity_id', $activityId)->exists()) {
                    $validator->errors()->add('activity_id', 'Anda sudah menghantar maklum balas untuk aktiviti ini.');
                }
            },
        ];
    }
}
