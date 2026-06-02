<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJoiningGroupRestrictionRequest;
use App\Models\JoiningGroupRestriction;
use App\Models\TableJoiningGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class JoiningGroupRestrictionController extends Controller
{
    public function index(TableJoiningGroup $group): Response
    {
        $group->load('restaurantTables');

        return inertia('Admin/Tables/Groups/Restrictions', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'tables' => $group->restaurantTables->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]),
            ],
            'restrictions' => $group->restrictions()
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn (JoiningGroupRestriction $r) => [
                    'id' => $r->id,
                    'day_of_week' => $r->day_of_week,
                    'start_time' => substr($r->start_time, 0, 5),
                    'end_time' => substr($r->end_time, 0, 5),
                ]),
        ]);
    }

    public function store(StoreJoiningGroupRestrictionRequest $request, TableJoiningGroup $group): RedirectResponse
    {
        $data = $request->validated();

        $overlaps = $group->restrictions()
            ->where(function ($q) use ($data): void {
                $q->whereNull('day_of_week')
                    ->orWhere('day_of_week', $data['day_of_week'] ?? null)
                    ->orWhereNull('day_of_week');
            })
            ->where('start_time', '<', $data['end_time'].':00')
            ->where('end_time', '>', $data['start_time'].':00')
            ->exists();

        if ($overlaps) {
            return back()->withErrors([
                'start_time' => 'A restriction already covers this day and time range.',
            ])->withInput();
        }

        $group->restrictions()->create([
            'day_of_week' => $data['day_of_week'] ?? null,
            'start_time' => $data['start_time'].':00',
            'end_time' => $data['end_time'].':00',
        ]);

        return back()->with('success', 'Restriction added.');
    }

    public function destroy(TableJoiningGroup $group, JoiningGroupRestriction $restriction): RedirectResponse
    {
        abort_if($restriction->table_joining_group_id !== $group->id, 404);

        $restriction->delete();

        return back()->with('success', 'Restriction removed.');
    }
}
