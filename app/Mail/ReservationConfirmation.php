<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly string $cancellationUrl;

    public function __construct(public readonly Reservation $reservation)
    {
        $this->cancellationUrl = route('reservations.cancel', [
            $reservation,
            $reservation->cancellation_token,
        ]);
    }

    public function envelope(): Envelope
    {
        $bcc = config('mail.admin_bcc')
            ? [new Address(config('mail.admin_bcc'))]
            : [];

        return new Envelope(
            subject: 'Your reservation is confirmed — '.$this->reservation->reference_number,
            bcc: $bcc,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reservation-confirmation',
        );
    }
}
