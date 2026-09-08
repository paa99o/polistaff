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
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at', 'before_or_equal:date_time'],
            'attendance_opens_at' => ['nullable', 'date', 'before_or_equal:date_time'],
            'attendance_closes_at' => ['nullable', 'date', 'after_or_equal:attendance_opens_at', 'after_or_equal:date_time'],
            'status' => ['required', 'in:draft,pending_approval,approved,cancelled'],
            'evidence_photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_closes_at.after_or_equal' => 'Registration Closes mesti sama atau selepas Registration Opens.',
            'registration_closes_at.before_or_equal' => 'Registration Closes mesti pada atau sebelum tarikh aktiviti.',
            'attendance_opens_at.before_or_equal' => 'Attendance Opens mesti pada atau sebelum tarikh aktiviti.',
            'attendance_closes_at.after_or_equal' => 'Attendance Closes mesti sama atau selepas Attendance Opens.',
            'evidence_photo.image' => 'Foto bukti aktiviti mesti dalam format gambar.',
            'evidence_photo.max' => 'Foto bukti aktiviti tidak boleh melebihi 4MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tajuk',
            'description' => 'penerangan',
            'date_time' => 'tarikh dan masa',
            'location' => 'lokasi',
            'max_participants' => 'maksimum peserta',
            'registration_opens_at' => 'registration opens',
            'registration_closes_at' => 'registration closes',
            'attendance_opens_at' => 'attendance opens',
            'attendance_closes_at' => 'attendance closes',
            'status' => 'status',
            'evidence_photo' => 'foto bukti aktiviti',
        ];
    }
}
