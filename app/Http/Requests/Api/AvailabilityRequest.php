<?php

namespace App\Http\Requests\Api;

use App\Models\OperatingHours;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->hasAny(['date', 'time'])) {
                return;
            }

            $date = Carbon::parse($this->input('date'));
            $time = Carbon::parse($this->input('time'));

            $dayOfWeek = (int) $date->dayOfWeek;

            /** @var OperatingHours|null $hours */
            $hours = OperatingHours::where('day_of_week', $dayOfWeek)->first();

            if (! $hours || ! $hours->isOpen($time)) {
                $validator->errors()->add('time', 'The selected time is outside operating hours.');
            }
        });
    }
}
