<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Donation;
use App\Models\ExpenseClaim;
use App\Models\PaymentSubmission;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Support\Str;

class PortalNotificationReconciler
{
    private const ACTIONABLE_TITLES = [
        'Permohonan aktiviti baharu',
        'Aktiviti menunggu kelulusan',
        'Aktiviti disahkan bendahari',
        'Bukti bayaran yuran baharu',
        'Permohonan ahli baharu',
        'Permohonan ahli dihantar semula',
        'Tuntutan baharu menunggu semakan',
        'Tuntutan disahkan bendahari',
        'Tuntutan menunggu kelulusan admin',
        'Permohonan sumbangan baharu',
        'Permohonan sumbangan disokong bendahari',
        'Sumbangan menunggu kelulusan admin',
    ];

    public function reconcileFor(User $user): void
    {
        $notifications = PortalNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->whereIn('title', self::ACTIONABLE_TITLES)
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        $pendingApplicantNames = null;

        foreach ($notifications as $notification) {
            $isStillActionable = match ($notification->title) {
                'Permohonan aktiviti baharu' => $user->hasRole('treasurer')
                    && $this->recordStatus($notification, '/activities/(\d+)', Activity::class) === 'pending_approval',
                'Aktiviti menunggu kelulusan' => $user->hasRole('admin')
                    && $this->recordStatus($notification, '/activities/(\d+)', Activity::class) === 'treasurer_verified',
                'Aktiviti disahkan bendahari' => $this->recordStatus($notification, '/activities/(\d+)', Activity::class) === 'treasurer_verified',
                'Bukti bayaran yuran baharu' => $user->hasRole('treasurer')
                    && $this->recordStatus($notification, '/payments/(\d+)', PaymentSubmission::class) === 'pending',
                'Permohonan ahli baharu', 'Permohonan ahli dihantar semula' => $user->hasRole('admin')
                    && $this->membershipIsPending($notification, $pendingApplicantNames),
                'Tuntutan baharu menunggu semakan' => $user->hasRole('treasurer')
                    && $this->recordStatus($notification, '/claims/(\d+)', ExpenseClaim::class) === 'pending',
                'Tuntutan disahkan bendahari' => $this->recordStatus($notification, '/claims/(\d+)', ExpenseClaim::class) === 'treasurer_verified',
                'Tuntutan menunggu kelulusan admin' => $user->hasRole('admin')
                    && $this->recordStatus($notification, '/claims/(\d+)', ExpenseClaim::class) === 'treasurer_verified',
                'Permohonan sumbangan baharu' => $user->hasRole('treasurer')
                    && $this->recordStatus($notification, '/donations/(\d+)', Donation::class) === 'pending',
                'Permohonan sumbangan disokong bendahari' => $this->recordStatus($notification, '/donations/(\d+)', Donation::class) === 'treasurer_verified',
                'Sumbangan menunggu kelulusan admin' => $user->hasRole('admin')
                    && $this->recordStatus($notification, '/donations/(\d+)', Donation::class) === 'treasurer_verified',
                default => true,
            };

            if (! $isStillActionable) {
                $notification->update(['is_read' => true]);
            }
        }
    }

    private function recordStatus(PortalNotification $notification, string $pathPattern, string $model): ?string
    {
        $path = parse_url($notification->link ?? '', PHP_URL_PATH) ?: '';

        if (! preg_match('~^'.$pathPattern.'/?$~', $path, $matches)) {
            return null;
        }

        return $model::query()->whereKey((int) $matches[1])->value('status');
    }

    private function membershipIsPending(PortalNotification $notification, ?array &$pendingApplicantNames): bool
    {
        $parts = parse_url($notification->link ?? '');
        $query = [];
        parse_str($parts['query'] ?? '', $query);

        if (isset($query['applicant']) && ctype_digit((string) $query['applicant'])) {
            return User::query()
                ->whereKey((int) $query['applicant'])
                ->where('membership_status', 'pending')
                ->exists();
        }

        $pendingApplicantNames ??= User::query()
            ->where('membership_status', 'pending')
            ->pluck('name')
            ->all();

        foreach ($pendingApplicantNames as $name) {
            if (Str::startsWith($notification->message, $name.' telah')) {
                return true;
            }
        }

        return false;
    }
}
