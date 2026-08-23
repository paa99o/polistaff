<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'date_time', 'location', 'max_participants', 'registration_opens_at', 'registration_closes_at', 'attendance_opens_at', 'attendance_closes_at', 'status', 'qr_code_token', 'evidence_photo_path'];

    protected function casts(): array
    {
        return ['date_time' => 'datetime', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime', 'attendance_opens_at' => 'datetime', 'attendance_closes_at' => 'datetime'];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'registered');
    }

    public function waitlistedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'waitlisted');
    }

    public function hasCapacity(): bool
    {
        return $this->max_participants === null || $this->activeRegistrations()->count() < $this->max_participants;
    }

    public function registrationIsOpen(): bool
    {
        return $this->status === 'approved'
            && ($this->registration_opens_at === null || now()->greaterThanOrEqualTo($this->registration_opens_at))
            && ($this->registration_closes_at === null || now()->lessThanOrEqualTo($this->registration_closes_at));
    }

    public function attendanceIsOpen(): bool
    {
        return $this->status === 'approved'
            && ($this->attendance_opens_at === null || now()->greaterThanOrEqualTo($this->attendance_opens_at))
            && ($this->attendance_closes_at === null || now()->lessThanOrEqualTo($this->attendance_closes_at));
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
