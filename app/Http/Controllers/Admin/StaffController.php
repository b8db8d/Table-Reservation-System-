<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        $staff = User::role([Role::Manager->value, Role::Staff->value])
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'is_active' => $user->is_active,
            ]);

        return Inertia::render('Admin/Staff/Index', ['staff' => $staff]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Staff/Create');
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => true,
        ]);

        $user->assignRole($request->validated('role'));

        return redirect()->route('admin.staff.index');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active ? 'Account activated.' : 'Account deactivated.';

        return back()->with('success', $message);
    }
}
