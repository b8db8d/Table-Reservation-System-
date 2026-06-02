<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvailabilityRequest;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availabilityService) {}

    public function __invoke(AvailabilityRequest $request): JsonResponse
    {
        $date = Carbon::parse($request->validated('date'));
        $time = Carbon::parse($request->validated('time'));
        $guests = (int) $request->validated('guests');

        $availability = $this->availabilityService->getAvailableCapacity($date, $time);

        $suitableTables = $availability['tables']->filter(
            fn ($table) => $table->capacity >= $guests
        );

        $suitableGroups = $availability['groups']->filter(
            fn ($group) => $group->restaurantTables->sum('capacity') >= $guests
                && $guests >= $group->min_guests
        );

        $available = $suitableTables->isNotEmpty() || $suitableGroups->isNotEmpty();

        return response()->json([
            'available' => $available,
            'individual_tables' => $suitableTables->map(fn ($table) => [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
            ])->values(),
            'joining_groups' => $suitableGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'capacity' => $group->restaurantTables->sum('capacity'),
                'min_guests' => $group->min_guests,
            ])->values(),
        ]);
    }
}
