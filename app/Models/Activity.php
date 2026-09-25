<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'activity_type', 'program_category', 'organizing_unit', 'person_in_charge', 'description', 'date_time', 'end_time', 'location', 'max_participants', 'expected_participants', 'participant_criteria', 'implementation_mode', 'proposal_data', 'registration_opens_at', 'registration_closes_at', 'status', 'qr_code_token', 'evidence_photo_path', 'report_photo_path', 'created_by', 'reviewed_by', 'reviewed_at', 'review_notes', 'treasurer_verified_by', 'treasurer_verified_at', 'treasurer_notes'];

    protected function casts(): array
    {
        return ['date_time' => 'datetime', 'end_time' => 'datetime', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime', 'reviewed_at' => 'datetime', 'treasurer_verified_at' => 'datetime', 'expected_participants' => 'integer', 'proposal_data' => 'array'];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function evidencePhotos(): HasMany
    {
        return $this->hasMany(ActivityEvidencePhoto::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function paperworkVersions(): HasMany
    {
        return $this->hasMany(ActivityPaperworkVersion::class)->orderByDesc('version');
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
            && filled($this->qr_code_token)
            && now()->greaterThanOrEqualTo($this->date_time)
            && now()->lessThanOrEqualTo($this->end_time ?? $this->date_time);
    }


    public function guestRegistrations(): HasMany
    {
        return $this->hasMany(GuestActivityRegistration::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treasurerVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'treasurer_verified_by');
    }

    public function isFinished(): bool
    {
        return now()->greaterThan($this->end_time ?? $this->date_time);
    }
}
