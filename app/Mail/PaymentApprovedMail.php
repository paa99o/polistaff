<?php

namespace App\Mail;

use App\Models\PaymentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Mail\Concerns\TracksEmailDelivery;

class PaymentApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use TracksEmailDelivery;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public PaymentSubmission $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Polistaff] Resit bayaran '.$this->payment->transaction?->receipt_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-approved',
        );
    }
}
