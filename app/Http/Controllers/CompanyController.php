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
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyController extends Controller
{
    public function index(DealershipIndexRequest $request): Response
    {
        $scope = $request->input('scope');
        if (! in_array($scope, ['mine', 'all'], true)) {
            $scope = 'mine';
        }

        $includeImported = $request->boolean('include_imported');
        $status = $request->input('status');

        $applyVisibilityFilters = function ($query) use ($request, $scope, $includeImported): void {
            if ($scope === 'mine') {
                $query->forUser($request->user());
            }

            if (! $includeImported) {
                $query->whereNot('status', 'imported');
            }
        };

        $dealershipQuery = Company::query();
        $applyVisibilityFilters($dealershipQuery);

        $dealershipQuery
            ->search($request->input('search'));

        if ($status) {
            if ($includeImported) {
                $dealershipQuery->whereIn('status', [$status, 'imported']);
            } else {
                $dealershipQuery->where('status', $status);
            }
        }

        $dealerships = $dealershipQuery
            ->withRating($request->input('rating'))
            ->withType($request->input('type'))
            ->sortBy($request->input('sort'), $request->input('direction'))
            ->select('id', 'name', 'city', 'state', 'status', 'rating')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($dealership) => DealershipResource::make($dealership)->resolve());

        $typeOptionsQuery = Company::query();
        $applyVisibilityFilters($typeOptionsQuery);

        $typeOptions = $typeOptionsQuery
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->filter()
            ->values()
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => Str::headline($type),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'companies' => $dealerships,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'rating' => $request->input('rating', ''),
                'type' => $request->input('type', ''),
                'scope' => $scope === 'mine' ? '' : 'all',
                'include_imported' => $includeImported ? '1' : '',
                'sort' => $request->input('sort', ''),
                'direction' => $request->input('direction', 'asc'),
            ],
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
                'types' => $typeOptions,
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
