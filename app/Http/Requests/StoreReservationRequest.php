<?php

namespace App\Http\Requests;

use App\Models\OperatingHours;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class StoreReservationRequest extends FormRequest
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
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->hasAny(['reservation_date', 'reservation_time'])) {
                return;
            }

            $date = Carbon::parse($this->input('reservation_date'));
            $time = Carbon::parse($this->input('reservation_time'));

            $hours = OperatingHours::where('day_of_week', (int) $date->dayOfWeek)->first();

            if (! $hours || ! $hours->isOpen($time)) {
                $validator->errors()->add('reservation_time', 'The selected time is outside operating hours.');
            }
        });
    }
}
