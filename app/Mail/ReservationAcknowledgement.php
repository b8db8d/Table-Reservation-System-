<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationAcknowledgement extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your reservation has been received',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reservation-acknowledgement',
        );
    }
}
