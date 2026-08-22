<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('treasurer', 'chairman', 'admin');
    }

    public function reverse(User $user, Transaction $transaction): bool
    {
        return $this->manage($user) && $transaction->status === 'active';
    }
}
