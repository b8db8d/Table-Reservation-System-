<?php

namespace App\Listeners;

use App\Events\ReservationRejected;
use App\Mail\ReservationRejection;
use Illuminate\Support\Facades\Mail;

class SendRejectionEmail
{
    public function handle(ReservationRejected $event): void
    {
        Mail::to($event->reservation->email)
            ->queue(new ReservationRejection($event->reservation));
    }
}
