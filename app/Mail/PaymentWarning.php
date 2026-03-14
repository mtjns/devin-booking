<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentWarning extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public int $daysRemaining;

    public function __construct(Booking $booking, int $daysRemaining)
    {
        $this->booking = $booking;
        $this->daysRemaining = $daysRemaining;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Upozornění: Blížící se splatnost rezervace Děvína',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-warning',
        );
    }
}