<?php

namespace App\Policies;

use App\Models\MemberDocument;
use App\Models\User;

class MemberDocumentPolicy
{
    public function view(User $user, MemberDocument $document): bool
    {
        return $document->user_id === $user->id || $user->hasRole('admin');
    }

    public function delete(User $user, MemberDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
