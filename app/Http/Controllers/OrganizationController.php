<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class OrganizationController extends Controller
{
    public function store(CreateOrganizationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Organization::class);

        $organization = Organization::create([
            'name' => $request->name,
        ]);

        auth()->user()->organizations()->attach($organization);

        return back()->with('success', 'Organization created successfully.');
    }

    public function switch(Organization $organization): RedirectResponse
    {
        abort_unless(auth()->user()->organizations()->where('organizations.id', $organization->id)->exists(), 403);

        auth()->user()->update(['current_organization_id' => $organization->id]);

        return back();
    }
}
