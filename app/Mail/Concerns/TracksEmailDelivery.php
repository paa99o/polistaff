<?php

namespace App\Mail\Concerns;

use App\Models\EmailDelivery;
use Illuminate\Mail\Mailables\Headers;
use Throwable;

trait TracksEmailDelivery
{
    public ?int $emailDeliveryId = null;

    public function headers(): Headers
    {
        return new Headers(text: [
            'X-Polistaff-Email-Delivery-ID' => (string) ($this->emailDeliveryId ?? ''),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        if ($this->emailDeliveryId) {
            $delivery = EmailDelivery::with('notification')->find($this->emailDeliveryId);

            if (! $delivery) {
                return;
            }

            $delivery->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            $delivery->notification?->update([
                'email_status' => 'failed',
                'email_error' => $exception->getMessage(),
            ]);
        }
    }
}
