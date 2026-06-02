<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperatingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'hours.*.is_closed' => ['required', 'boolean'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i', 'required_if:hours.*.is_closed,false'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i', 'required_if:hours.*.is_closed,false', 'after:hours.*.open_time'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v): void {
            foreach ($this->input('hours', []) as $i => $row) {
                if (! empty($row['is_closed'])) {
                    continue;
                }

                if (! empty($row['open_time']) && ! empty($row['close_time'])
                    && $row['open_time'] >= $row['close_time']
                ) {
                    $v->errors()->add("hours.{$i}.close_time", 'Close time must be after open time.');
                }
            }
        });
    }
}
