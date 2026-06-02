<x-mail::message>
# Reservation Cancelled

Hi {{ $reservation->first_name }},

Your reservation has been successfully cancelled. We're sorry we won't be able to see you.

**Reference:** {{ $reservation->reference_number }}

**Date:** {{ $reservation->reservation_date->format('F j, Y') }}

**Time:** {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

**Guests:** {{ $reservation->guest_count }}

We hope to welcome you another time. Please visit our website to make a new reservation.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
