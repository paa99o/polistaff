<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmailAuditService
{
    public function sent(?User $recipient, string $event, ?Model $record = null, ?string $email = null): void
    {
        $recipientEmail = $email ?? $recipient?->email;

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'email-sent',
            'module' => 'Email Notification',
            'record_type' => $record ? $record::class : null,
            'record_id' => $record?->getKey(),
            'description' => 'Sent '.$event.' email to '.($recipientEmail ?: 'unknown recipient').'.',
            'changes' => [
                'event' => $event,
                'recipient_user_id' => $recipient?->id,
                'recipient_email' => $recipientEmail,
            ],
            'ip_address' => request()?->ip(),
        ]);
    }
}
