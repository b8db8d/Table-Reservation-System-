<?php

namespace App\Listeners;

use App\Events\ReservationConfirmed;
use App\Mail\ReservationConfirmation;
use Illuminate\Support\Facades\Mail;

class SendConfirmationEmail
{
    public function handle(ReservationConfirmed $event): void
    {
        Mail::to($event->reservation->email)
            ->queue(new ReservationConfirmation($event->reservation));
    }
}
