<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DealershipIndexRequest;
use App\Http\Requests\DealershipUpdateRequest;
use App\Http\Resources\DealershipResource;
use App\Http\Resources\DealershipShowResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyController extends Controller
{
    public function index(DealershipIndexRequest $request): Response
    {
        $dealerships = Company::query()
            ->whereNot('status', 'imported')
            ->search($request->input('search'))
            ->withStatus($request->input('status'))
            ->withRating($request->input('rating'))
            ->sortBy($request->input('sort'), $request->input('direction'))
            ->select('id', 'name', 'city', 'state', 'status', 'rating')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($dealership) => DealershipResource::make($dealership)->resolve());

        return Inertia::render('Dashboard', [
            'companies' => $dealerships,
            'filters' => $request->only(['search', 'status', 'rating', 'sort', 'direction']),
            'filterOptions' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'ratings' => [
                    ['value' => 'hot', 'label' => 'Hot'],
                    ['value' => 'warm', 'label' => 'Warm'],
                    ['value' => 'cold', 'label' => 'Cold'],
                ],
            ],
        ]);
    }

    public function show(Company $company): Response
    {
        $company->load([
            'users' => fn ($query) => $query->select('id', 'name'),
            'stores',
            'contacts',
        ]);

        return Inertia::render('Company/Show', [
            'company' => DealershipShowResource::make($company)->resolve(),
            'allUsers' => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(DealershipUpdateRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->safe()->except(['user_ids']));

        if ($request->has('user_ids')) {
            $validated = $request->validate([
                'user_ids' => ['array'],
                'user_ids.*' => ['integer', 'exists:users,id'],
            ]);

            $company->users()->sync($validated['user_ids'] ?? []);
        }

        return redirect()
            ->route('company.show', $company)
            ->with('success', 'Company updated successfully.');
    }
}
