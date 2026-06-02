<x-mail::message>
# Reservation Confirmed!

Hi {{ $reservation->first_name }},

Great news — your reservation has been confirmed.

**Reference:** {{ $reservation->reference_number }}

**Date:** {{ $reservation->reservation_date->format('F j, Y') }}

**Time:** {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

**Guests:** {{ $reservation->guest_count }}

If your plans change, you can cancel your reservation using the link below. Please note that cancellations must be made in advance.

<x-mail::button :url="$cancellationUrl" color="error">
Cancel Reservation
</x-mail::button>

We look forward to seeing you!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
