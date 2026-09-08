<?php

namespace App\Mail;

use App\Models\PaymentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public PaymentSubmission $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Polistaff] Bayaran ditolak',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-rejected',
        );
    }
}
