<x-mail::message>
# Reservation Update

Hi {{ $reservation->first_name }},

Thank you for your reservation request. Unfortunately, we are unable to accommodate your booking at this time.

**Reference:** {{ $reservation->reference_number }}

**Date:** {{ $reservation->reservation_date->format('F j, Y') }}

**Time:** {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

**Guests:** {{ $reservation->guest_count }}

@if($reservation->rejection_reason)
**Reason:** {{ $reservation->rejection_reason }}
@endif

We apologise for any inconvenience. Please visit our website to check availability for another date.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
