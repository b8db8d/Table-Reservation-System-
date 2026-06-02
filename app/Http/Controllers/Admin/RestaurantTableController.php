<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRestaurantTableRequest;
use App\Http\Requests\Admin\UpdateRestaurantTableRequest;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class RestaurantTableController extends Controller
{
    public function index(): Response
    {
        $tables = RestaurantTable::orderBy('name')
            ->get(['id', 'name', 'capacity', 'is_active']);

        return inertia('Admin/Tables/Index', [
            'tables' => $tables,
        ]);
    }

    public function create(): Response
    {
        return inertia('Admin/Tables/Create');
    }

    public function store(StoreRestaurantTableRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        RestaurantTable::create($data);

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table created.');
    }

    public function edit(RestaurantTable $table): Response
    {
        return inertia('Admin/Tables/Edit', [
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'is_active' => $table->is_active,
            ],
        ]);
    }

    public function update(UpdateRestaurantTableRequest $request, RestaurantTable $table): RedirectResponse
    {
        $table->update($request->validated());

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table updated.');
    }

    public function destroy(RestaurantTable $table): RedirectResponse
    {
        $hasUpcoming = $table->reservations()
            ->where('status', ReservationStatus::Confirmed)
            ->where('reservation_date', '>=', today())
            ->exists();

        if ($hasUpcoming) {
            return back()->withErrors([
                'table' => 'Cannot delete a table with upcoming confirmed reservations.',
            ]);
        }

        $table->delete();

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table deleted.');
    }
}
