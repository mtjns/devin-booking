<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingUpdated extends Mailable
{
    use Queueable, SerializesModels;

    // Public properties are directly available as variables in the Blade template
    public Booking $booking;

    // Receives the specific booking database row that was just updated
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    // Defines the subject line that appears in the customer's inbox
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Změna ve Vaší rezervaci chaty',
        );
    }

    // Instructs Laravel which HTML file to compile for the email body
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-updated',
        );
    }
}