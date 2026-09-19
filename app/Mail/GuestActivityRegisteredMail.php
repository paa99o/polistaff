<?php

namespace App\Mail;

use App\Models\GuestActivityRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestActivityRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GuestActivityRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[POLIBEST] Pendaftaran aktiviti diterima');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.guest-activity-registered');
    }
}
