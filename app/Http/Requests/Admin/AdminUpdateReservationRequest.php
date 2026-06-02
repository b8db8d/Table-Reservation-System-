<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class AdminUpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', (new Phone)->international()],
            'guest_count' => ['required', 'integer', 'min:1', 'max:20'],
            'reservation_date' => ['required', 'date_format:Y-m-d'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(ReservationStatus::class)],
        ];
    }
}
