<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class StaffReservationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly string $confirmUrl;

    public readonly string $rejectUrl;

    public function __construct(public readonly Reservation $reservation)
    {
        $this->confirmUrl = URL::temporarySignedRoute(
            'reservations.deeplink.confirm',
            now()->addHours(72),
            ['reservation' => $reservation],
        );

        $this->rejectUrl = URL::temporarySignedRoute(
            'reservations.deeplink.reject',
            now()->addHours(72),
            ['reservation' => $reservation],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New reservation request — '.$this->reservation->reference_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.staff-reservation-notification',
        );
    }
}
