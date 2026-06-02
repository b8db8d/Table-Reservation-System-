<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Events\ReservationCancelled;
use App\Mail\ReservationCancelled as ReservationCancelledMail;
use App\Mail\StaffCancellationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendCancellationEmails
{
    public function handle(ReservationCancelled $event): void
    {
        Mail::to($event->reservation->email)
            ->queue(new ReservationCancelledMail($event->reservation));

        User::whereHas('roles', fn ($q) => $q->whereIn('name', [Role::Manager->value, Role::Staff->value]))
            ->each(function (User $staffMember) use ($event): void {
                Mail::to($staffMember->email)
                    ->queue(new StaffCancellationNotification($event->reservation));
            });
    }
}
