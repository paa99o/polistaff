<?php

namespace App\Mail;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Mail\Concerns\TracksEmailDelivery;

class ActivityCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use TracksEmailDelivery;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public Activity $activity) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Polistaff] Aktiviti dibatalkan: '.$this->activity->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activity-cancelled',
        );
    }
}
