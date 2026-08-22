<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function approve(User $user, Activity $activity): bool
    {
        return $user->hasRole('chairman', 'admin') && $activity->status !== 'approved';
    }
}
