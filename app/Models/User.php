<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'name',
        'ic_number',
        'email',
        'password',
        'role',
        'department',
        'phone',
        'address',
        'profile_photo_path',
        'theme_preference',
        'text_size_preference',
        'reduce_motion',
        'email_announcements',
        'email_activities',
        'email_finance',
        'email_fee_reminders',
        'membership_status',
        'membership_review_notes',
        'joined_date',
        'fee_balance',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    public function feeBills(): HasMany
    {
        return $this->hasMany(MemberFeeBill::class);
    }

    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    public function polimartItems(): HasMany
    {
        return $this->hasMany(PolimartItem::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function activityRegistrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function portalNotifications(): HasMany
    {
        return $this->hasMany(PortalNotification::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function wantsEmail(string $category): bool
    {
        $attribute = match ($category) {
            'announcements' => 'email_announcements',
            'activities' => 'email_activities',
            'finance' => 'email_finance',
            'fee_reminders' => 'email_fee_reminders',
            default => null,
        };

        return $attribute === null || (bool) $this->{$attribute};
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function missingProfileFields(): array
    {
        return collect($this->profileCompletionFields())
            ->filter(fn (string $attribute) => blank($this->{$attribute}))
            ->keys()
            ->all();
    }

    public function profileIsComplete(): bool
    {
        return count($this->missingProfileFields()) === 0;
    }

    public function scopeProfileComplete(Builder $query): Builder
    {
        foreach ($this->profileCompletionFields() as $attribute) {
            $query->whereNotNull($attribute)->where($attribute, '!=', '');
        }

        return $query;
    }

    public function scopeProfileIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            foreach ($this->profileCompletionFields() as $attribute) {
                $query->orWhereNull($attribute)->orWhere($attribute, '');
            }
        });
    }

    private function profileCompletionFields(): array
    {
        return [
            'Gambar profil' => 'profile_photo_path',
            'Nombor IC' => 'ic_number',
            'Jabatan' => 'department',
            'Telefon' => 'phone',
            'Alamat' => 'address',
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joined_date' => 'date',
            'fee_balance' => 'decimal:2',
            'reduce_motion' => 'boolean',
            'email_announcements' => 'boolean',
            'email_activities' => 'boolean',
            'email_finance' => 'boolean',
            'email_fee_reminders' => 'boolean',
        ];
    }
}
