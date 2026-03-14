<?php

namespace App\Mail;

use App\Models\Booking;
use App\Settings\GeneralSettings;
use Defr\QRPlatba\QRPlatba;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCreatedConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potvrzení rezervace chaty a pokyny k platbě',
        );
    }

    public function content(): Content
    {
        $settings = app(GeneralSettings::class);
        $remainingDeposit = max(0, $this->booking->deposit_amount - $this->booking->paid_amount);

        $qrPlatba = new QRPlatba();
        $qrPlatba->setAccount($settings->bank_account_number . '/' . $settings->bank_code)
            ->setAmount($remainingDeposit)
            ->setVariableSymbol($this->booking->variable_symbol)
            ->setMessage('Rezervace - ' . $this->booking->customer_name)
            ->setCurrency('CZK');

        $base64 = explode(',', $qrPlatba->getDataUri())[1];
        $qrBinary = base64_decode($base64);

        return new Content(
            view: 'emails.booking-created-confirmation',
            with: [
                'qrImageBinary' => $qrBinary,
                'settings' => $settings,
            ],
        );
    }
}