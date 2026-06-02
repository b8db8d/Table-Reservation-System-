<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Mail\ReservationAcknowledgement;
use Illuminate\Support\Facades\Mail;

class SendAcknowledgementEmail
{
    public function handle(ReservationCreated $event): void
    {
        Mail::to($event->reservation->email)
            ->queue(new ReservationAcknowledgement($event->reservation));
    }
}
