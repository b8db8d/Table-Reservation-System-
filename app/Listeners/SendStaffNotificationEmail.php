<?php

namespace App\Listeners;

use App\Enums\Role;
use App\Events\ReservationCreated;
use App\Mail\StaffReservationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendStaffNotificationEmail
{
    public function handle(ReservationCreated $event): void
    {
        User::whereHas('roles', fn ($q) => $q->whereIn('name', [Role::Manager->value, Role::Staff->value]))
            ->each(function (User $staffMember) use ($event): void {
                Mail::to($staffMember->email)
                    ->queue(new StaffReservationNotification($event->reservation));
            });
    }
}
