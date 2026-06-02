<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTableJoiningGroupRequest;
use App\Http\Requests\Admin\UpdateTableJoiningGroupRequest;
use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class TableJoiningGroupController extends Controller
{
    public function index(): Response
    {
        $groups = TableJoiningGroup::with('restaurantTables')
            ->orderBy('id')
            ->get()
            ->map(fn (TableJoiningGroup $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'min_guests' => $g->min_guests,
                'combined_capacity' => $g->combinedCapacity(),
                'tables' => $g->restaurantTables->map(fn (RestaurantTable $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'capacity' => $t->capacity,
                ]),
            ]);

        return inertia('Admin/Tables/Groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function create(): Response
    {
        return inertia('Admin/Tables/Groups/Create', [
            'availableTables' => $this->ungroupedTables(),
        ]);
    }

    public function store(StoreTableJoiningGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $conflict = $this->findAlreadyGroupedTables($data['table_ids']);
        if ($conflict->isNotEmpty()) {
            return back()->withErrors([
                'table_ids' => 'One or more selected tables already belong to another group.',
            ])->withInput();
        }

        $group = TableJoiningGroup::create([
            'name' => $data['name'] ?? null,
            'min_guests' => $data['min_guests'],
        ]);
        $group->restaurantTables()->attach($data['table_ids']);

        return redirect()->route('admin.tables.groups.index')
            ->with('success', 'Joining group created.');
    }

    public function edit(TableJoiningGroup $group): Response
    {
        $group->load('restaurantTables');

        return inertia('Admin/Tables/Groups/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'min_guests' => $group->min_guests,
                'table_ids' => $group->restaurantTables->pluck('id')->toArray(),
            ],
            'availableTables' => $this->ungroupedTables($group->id),
        ]);
    }

    public function update(UpdateTableJoiningGroupRequest $request, TableJoiningGroup $group): RedirectResponse
    {
        $data = $request->validated();

        $conflict = $this->findAlreadyGroupedTables($data['table_ids'], $group->id);
        if ($conflict->isNotEmpty()) {
            return back()->withErrors([
                'table_ids' => 'One or more selected tables already belong to another group.',
            ])->withInput();
        }

        $group->update([
            'name' => $data['name'] ?? null,
            'min_guests' => $data['min_guests'],
        ]);
        $group->restaurantTables()->sync($data['table_ids']);

        return redirect()->route('admin.tables.groups.index')
            ->with('success', 'Joining group updated.');
    }

    public function destroy(TableJoiningGroup $group): RedirectResponse
    {
        $group->delete();

        return redirect()->route('admin.tables.groups.index')
            ->with('success', 'Joining group deleted.');
    }

    /** @param array<int> $tableIds */
    private function findAlreadyGroupedTables(array $tableIds, ?int $excludeGroupId = null)
    {
        return RestaurantTable::whereIn('id', $tableIds)
            ->whereHas('joiningGroups', function ($q) use ($excludeGroupId): void {
                if ($excludeGroupId) {
                    $q->where('table_joining_groups.id', '!=', $excludeGroupId);
                }
            })
            ->get();
    }

    private function ungroupedTables(?int $excludeGroupId = null)
    {
        return RestaurantTable::where('is_active', true)
            ->where(function ($q) use ($excludeGroupId): void {
                $q->whereDoesntHave('joiningGroups')
                    ->when($excludeGroupId, fn ($q) => $q->orWhereHas('joiningGroups', fn ($q) => $q->where('table_joining_groups.id', $excludeGroupId)));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);
    }
}
