<?php

namespace App\Services;

use App\Models\EmailDelivery;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDeliveryService
{
    public function send(?User $user, string $event, Mailable $mailable, ?Model $record = null): void
    {
        $recipient = $user?->email;
        if (! $recipient) {
            return;
        }

        $delivery = EmailDelivery::create([
            'user_id' => $user?->id,
            'notification_id' => $record instanceof PortalNotification ? $record->id : null,
            'event' => $event,
            'recipient' => $recipient,
            'mailable' => $mailable::class,
            'status' => 'queued',
        ]);

        if ($record instanceof PortalNotification) {
            $record->update(['email_recipient' => $recipient, 'email_status' => 'queued', 'email_attempts' => $record->email_attempts + 1, 'email_error' => null]);
        }

        if (property_exists($mailable, 'emailDeliveryId')) {
            $mailable->emailDeliveryId = $delivery->id;
        }

        try {
            Mail::to($recipient)->send($mailable);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'error' => $exception->getMessage()]);

            $delivery->notification?->update([
                'email_status' => 'failed',
                'email_error' => $exception->getMessage(),
            ]);
        }
    }
}
