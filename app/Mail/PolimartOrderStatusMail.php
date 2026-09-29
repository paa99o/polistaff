<?php

namespace App\Mail;

use App\Models\PolimartOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PolimartOrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PolimartOrder $order, public string $trackingUrl) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->order->payment_status) {
            'rejected' => '[POLIBEST] Bukti bayaran PoliMart perlu dihantar semula',
            'paid' => '[POLIBEST] Bayaran PoliMart disahkan',
            'refund_required', 'refunded' => '[POLIBEST] Status pemulangan bayaran PoliMart',
            default => $this->order->status === 'pending'
                ? '[POLIBEST] Pesanan PoliMart diterima'
                : '[POLIBEST] Status pesanan PoliMart dikemas kini',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.polimart-order-status');
    }
}
