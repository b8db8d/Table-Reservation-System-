<?php

namespace App\Listeners;

use App\Events\AvailabilityUpdated;

class BroadcastAvailabilityUpdate
{
    public function handle(object $event): void
    {
        AvailabilityUpdated::dispatch();
    }
}
