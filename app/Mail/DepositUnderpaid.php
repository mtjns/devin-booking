<?php

namespace App\Mail;

use App\Models\Booking;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DepositUnderpaid extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public int $missingAmount;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;

        // Calculates the required deposit and subtracts what was already paid
        // to pass the exact missing amount to the email template
        $this->missingAmount = max(0, $this->booking->deposit_amount - $this->booking->paid_amount);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nedoplatek zálohy - Rezervace chaty Děvín',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deposit-underpaid',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}