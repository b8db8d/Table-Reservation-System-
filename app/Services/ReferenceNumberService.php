<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;

class ReferenceNumberService
{
    public function generate(): string
    {
        do {
            $candidate = $this->make();
        } while (Reservation::where('reference_number', $candidate)->exists());

        return $candidate;
    }

    private function make(): string
    {
        $suffix = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        return 'RES-'.Carbon::now()->format('Ymd').'-'.$suffix;
    }
}
