<x-mail::message>
# New Reservation Request

A new reservation has been submitted and is awaiting your review.

**Reference:** {{ $reservation->reference_number }}

**Guest:** {{ $reservation->first_name }} {{ $reservation->last_name }}

**Contact:** {{ $reservation->email }} · {{ $reservation->phone }}

**Date:** {{ $reservation->reservation_date->format('F j, Y') }}

**Time:** {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}

**Guests:** {{ $reservation->guest_count }}

@if($reservation->notes)
**Notes:** {{ $reservation->notes }}
@endif

<x-mail::button :url="$confirmUrl" color="success">
Confirm Reservation
</x-mail::button>

<x-mail::button :url="$rejectUrl" color="error">
Reject Reservation
</x-mail::button>

These links expire in 72 hours.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
