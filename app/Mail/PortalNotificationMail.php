<?php

namespace App\Mail;

use App\Models\PortalNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Mail\Concerns\TracksEmailDelivery;

class PortalNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use TracksEmailDelivery;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public PortalNotification $notification) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Polistaff] '.$this->notification->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-notification',
        );
    }

}
