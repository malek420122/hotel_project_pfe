<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public $reservation, public $chambre)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirmation de reservation ' . $this->reservation->reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-confirmation');
    }
}
