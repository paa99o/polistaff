<?php

namespace App\Policies;

use App\Models\ExpenseClaim;
use App\Models\User;

class ExpenseClaimPolicy
{
    public function view(User $user, ExpenseClaim $claim): bool
    {
        return $user->hasRole('treasurer', 'admin') || $claim->user_id === $user->id;
    }

    public function verify(User $user, ExpenseClaim $claim): bool
    {
        return $user->hasRole('treasurer') && $claim->status === 'pending';
    }

    public function approve(User $user, ExpenseClaim $claim): bool
    {
        return $user->hasRole('admin') && $claim->status === 'treasurer_verified';
    }

    public function reject(User $user, ExpenseClaim $claim): bool
    {
        return ($user->hasRole('treasurer') && $claim->status === 'pending')
            || ($user->hasRole('admin') && $claim->status === 'treasurer_verified');
    }
}
