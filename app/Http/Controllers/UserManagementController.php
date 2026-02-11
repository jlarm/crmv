<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->is_admin, 403);

        $users = User::query()
            ->with(['organizations:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'current_organization_id' => $user->current_organization_id,
                'organizations' => $user->organizations
                    ->map(fn (Organization $organization): array => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                    ])
                    ->values()
                    ->all(),
            ]);

        $organizations = Organization::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_admin' => (bool) ($validated['is_admin'] ?? false),
                'current_organization_id' => $validated['organization_ids'][0] ?? null,
            ]);

            $user->organizations()->sync($validated['organization_ids']);
        });

        return back()->with('success', 'User created successfully.');
    }

    public function updateOrganizations(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        $validated = $request->validate([
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
        ]);

        $organizationIds = $validated['organization_ids'];

        $user->organizations()->sync($organizationIds);

        if (! in_array((int) $user->current_organization_id, $organizationIds, true)) {
            $user->update([
                'current_organization_id' => $organizationIds[0] ?? null,
            ]);
        }

        return back()->with('success', 'User organizations updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        if ((int) $request->user()->id === (int) $user->id) {
            return back()->withErrors([
                'delete' => 'You cannot delete your own account from this screen.',
            ]);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
