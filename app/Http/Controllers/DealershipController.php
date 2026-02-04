<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DealershipIndexRequest;
use App\Http\Requests\DealershipUpdateRequest;
use App\Http\Resources\DealershipResource;
use App\Http\Resources\DealershipShowResource;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class DealershipController extends Controller
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

    public function show(Company $dealership): Response
    {
        $dealership->load([
            'users' => fn ($query) => $query->select('id', 'name'),
            'stores',
            'contacts',
        ]);

        return Inertia::render('Company/Show', [
            'company' => DealershipShowResource::make($dealership)->resolve(),
        ]);
    }

    public function update(DealershipUpdateRequest $request, Company $dealership): RedirectResponse
    {
        $dealership->update($request->validated());

        return redirect()
            ->route('dealerships.show', $dealership)
            ->with('success', 'Dealership updated successfully.');
    }
}
