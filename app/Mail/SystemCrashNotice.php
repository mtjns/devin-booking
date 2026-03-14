<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SystemCrashNotice extends Mailable
{
    use Queueable, SerializesModels;

    public string $errorMessage;
    public string $errorClass;
    public string $errorFile;
    public int $errorLine;
    public string $source;
    public ?string $jobContext;
    public ?string $actionRequired;

    // Accepts the exception interface and a string identifying where the crash occurred
    public function __construct(Throwable $exception, string $source = 'Application', ?string $jobContext = null, ?string $actionRequired = null)
    {
        $this->errorMessage = $exception->getMessage() ?: 'No exception message provided.';
        $this->errorClass = get_class($exception);
        $this->errorFile = $exception->getFile();
        $this->errorLine = $exception->getLine();
        $this->source = $source;
        $this->jobContext = $jobContext;
        $this->actionRequired = $actionRequired ?? 'Zkontrolujte logy aplikace pro více informací. (Prohlédněte system log nebo Laravel log)';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "CRITICAL ERROR: Děvín Booking System ({$this->source})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system.crash_notice',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}