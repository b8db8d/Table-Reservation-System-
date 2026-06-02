<?php

namespace App\Models;

use Database\Factories\TableJoiningGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'min_guests'])]
class TableJoiningGroup extends Model
{
    /** @use HasFactory<TableJoiningGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_guests' => 'integer',
        ];
    }

    public function restaurantTables(): BelongsToMany
    {
        return $this->belongsToMany(RestaurantTable::class, 'table_joining_group_restaurant_table');
    }

    public function restrictions(): HasMany
    {
        return $this->hasMany(JoiningGroupRestriction::class);
    }

    public function combinedCapacity(): int
    {
        return $this->restaurantTables->sum('capacity');
    }
}
