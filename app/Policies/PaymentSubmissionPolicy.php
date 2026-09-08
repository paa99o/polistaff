<?php

namespace App\Policies;

use App\Models\PaymentSubmission;
use App\Models\User;

class PaymentSubmissionPolicy
{
    public function view(User $user, PaymentSubmission $payment): bool
    {
        return $user->hasRole('treasurer', 'chairman', 'admin') || $payment->user_id === $user->id;
    }

    public function review(User $user, PaymentSubmission $payment): bool
    {
        return $user->hasRole('treasurer', 'admin') && $payment->status === 'pending';
    }
}
