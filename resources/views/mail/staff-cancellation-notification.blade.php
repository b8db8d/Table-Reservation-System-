<x-mail::message>
# Reservation Cancelled

A guest has cancelled their reservation.

**Reference:** {{ $reservation->reference_number }}

**Guest:** {{ $reservation->first_name }} {{ $reservation->last_name }}

**Contact:** {{ $reservation->email }} · {{ $reservation->phone }}

**Date:** {{ $reservation->reservation_date->format('F j, Y') }}

**Time:** {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

**Guests:** {{ $reservation->guest_count }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
