<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class StayReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function __construct(public $reservation, public string $firstName)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Rappel de sejour - Reservation ' . $this->reservation->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stay-reminder');
    }
}
