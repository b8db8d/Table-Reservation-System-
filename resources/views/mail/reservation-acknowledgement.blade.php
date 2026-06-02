<x-mail::message>
# Reservation Received

Hi {{ $reservation->first_name }},

Thank you for your reservation request. We've received your booking and our team will review it shortly.

**Reference number:** {{ $reservation->reference_number }}

**Details:**
- **Date:** {{ $reservation->reservation_date->format('F j, Y') }}
- **Time:** {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}
- **Guests:** {{ $reservation->guest_count }}

You will receive a confirmation email once our team reviews your booking.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
