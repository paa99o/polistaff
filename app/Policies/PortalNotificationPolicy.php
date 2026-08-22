<?php

namespace App\Policies;

use App\Models\PortalNotification;
use App\Models\User;

class PortalNotificationPolicy
{
    public function view(User $user, PortalNotification $notification): bool
    {
        return $notification->user_id === null || $notification->user_id === $user->id;
    }

    public function send(User $user): bool
    {
        return $user->hasRole('admin', 'chairman');
    }
}
