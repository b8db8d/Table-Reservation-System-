<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\OperatingHoursFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['day_of_week', 'open_time', 'close_time', 'is_closed'])]
class OperatingHours extends Model
{
    /** @use HasFactory<OperatingHoursFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    public function isOpen(Carbon $time): bool
    {
        if ($this->is_closed || $this->open_time === null || $this->close_time === null) {
            return false;
        }

        $formatted = $time->format('H:i:s');

        return $formatted >= $this->open_time && $formatted < $this->close_time;
    }
}
