<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * Returns free individual tables and available joining groups for the given date+time slot.
     *
     * @return array{tables: Collection<int, RestaurantTable>, groups: Collection<int, TableJoiningGroup>}
     */
    public function getAvailableCapacity(CarbonInterface $date, CarbonInterface $time): array
    {
        $slot = Carbon::create($date->year, $date->month, $date->day, $time->hour, $time->minute, $time->second);

        $activeTables = RestaurantTable::active()->get();
        $activeTableIds = $activeTables->pluck('id');

        $lockedTableIds = DB::table('reservation_restaurant_table')
            ->join('reservations', 'reservations.id', '=', 'reservation_restaurant_table.reservation_id')
            ->whereDate('reservations.reservation_date', $date->toDateString())
            ->where('reservations.reservation_time', $time->format('H:i:s'))
            ->where('reservations.status', ReservationStatus::Confirmed->value)
            ->whereIn('reservation_restaurant_table.restaurant_table_id', $activeTableIds)
            ->pluck('restaurant_table_id');

        $freeTables = $activeTables->whereNotIn('id', $lockedTableIds);
        $freeTableIds = $freeTables->pluck('id');

        $availableGroups = TableJoiningGroup::with(['restaurantTables', 'restrictions'])
            ->get()
            ->filter(function (TableJoiningGroup $group) use ($freeTableIds, $slot): bool {
                $groupTableIds = $group->restaurantTables->pluck('id');

                if ($groupTableIds->isEmpty()) {
                    return false;
                }

                foreach ($groupTableIds as $tableId) {
                    if (! $freeTableIds->contains($tableId)) {
                        return false;
                    }
                }

                foreach ($group->restrictions as $restriction) {
                    if ($restriction->appliesTo($slot)) {
                        return false;
                    }
                }

                return true;
            });

        return [
            'tables' => $freeTables->values(),
            'groups' => $availableGroups->values(),
        ];
    }
}
