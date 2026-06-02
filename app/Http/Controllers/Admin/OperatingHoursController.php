<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOperatingHoursRequest;
use App\Models\OperatingHours;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class OperatingHoursController extends Controller
{
    public function index(): Response
    {
        $hours = OperatingHours::orderBy('day_of_week')->get()->map(fn (OperatingHours $h) => [
            'id' => $h->id,
            'day_of_week' => $h->day_of_week,
            'open_time' => $h->open_time ? substr($h->open_time, 0, 5) : null,
            'close_time' => $h->close_time ? substr($h->close_time, 0, 5) : null,
            'is_closed' => $h->is_closed,
        ]);

        return inertia('Admin/Settings/OperatingHours', [
            'hours' => $hours,
        ]);
    }

    public function update(UpdateOperatingHoursRequest $request): RedirectResponse
    {
        foreach ($request->validated('hours') as $row) {
            OperatingHours::where('day_of_week', $row['day_of_week'])->update([
                'is_closed' => $row['is_closed'],
                'open_time' => $row['is_closed'] ? null : ($row['open_time'].':00'),
                'close_time' => $row['is_closed'] ? null : ($row['close_time'].':00'),
            ]);
        }

        return back()->with('success', 'Operating hours updated.');
    }
}
