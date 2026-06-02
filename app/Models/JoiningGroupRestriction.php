<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\JoiningGroupRestrictionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['table_joining_group_id', 'day_of_week', 'start_time', 'end_time'])]
class JoiningGroupRestriction extends Model
{
    /** @use HasFactory<JoiningGroupRestrictionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function tableJoiningGroup(): BelongsTo
    {
        return $this->belongsTo(TableJoiningGroup::class);
    }

    /** Returns true when this restriction blocks the given date + time. */
    public function appliesTo(Carbon $dateTime): bool
    {
        if ($this->day_of_week !== null && $this->day_of_week !== (int) $dateTime->dayOfWeek) {
            return false;
        }

        $time = $dateTime->format('H:i:s');

        return $time >= $this->start_time && $time < $this->end_time;
    }
}
