<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Services\ReferenceNumberService;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reference_number',
    'first_name',
    'last_name',
    'email',
    'phone',
    'guest_count',
    'reservation_date',
    'reservation_time',
    'status',
    'notes',
    'rejection_reason',
    'user_id',
    'confirmed_by',
    'confirmed_at',
    'rejected_by',
    'rejected_at',
    'cancelled_at',
    'cancellation_token',
])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation): void {
            if (empty($reservation->reference_number)) {
                $reservation->reference_number = app(ReferenceNumberService::class)->generate();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'confirmed_by', 'rejected_by', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'guest_count' => 'integer',
            'reservation_date' => 'date',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function restaurantTables(): BelongsToMany
    {
        return $this->belongsToMany(RestaurantTable::class, 'reservation_restaurant_table');
    }
}
