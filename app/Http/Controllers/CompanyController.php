<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DealershipIndexRequest;
use App\Http\Requests\DealershipUpdateRequest;
use App\Http\Resources\DealershipResource;
use App\Http\Resources\DealershipShowResource;
use App\Models\Company;
use App\Models\Progress;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $applyVisibilityFilters = function (Builder $query) use ($request, $scope, $includeImported): void {
            if ($scope === 'mine') {
                $query->forUser($request->user());
            }

            if (! $includeImported) {
                $query->whereNot('status', 'imported');
            }
        };

        $applyCompanyFilters = function (Builder $query) use ($applyVisibilityFilters, $request, $status, $includeImported): void {
            $applyVisibilityFilters($query);

            $query->search($request->input('search'))
                ->withRating($request->input('rating'))
                ->withType($request->input('type'));

            if (! $status) {
                return;
            }

            if ($includeImported) {
                $query->whereIn('status', [$status, 'imported']);
            } else {
                $query->where('status', $status);
            }
        };

        $dealershipQuery = Company::query();
        $applyCompanyFilters($dealershipQuery);

        $dealerships = $dealershipQuery
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

        $today = CarbonImmutable::today();
        $nextWeek = $today->addDays(7);

        $buildProgressQuery = function () use ($applyCompanyFilters): Builder {
            return Progress::query()
                ->with(['company:id,name', 'contact:id,name'])
                ->whereNull('completed_at')
                ->whereNotNull('date')
                ->whereHas('company', function (Builder $query) use ($applyCompanyFilters): void {
                    $applyCompanyFilters($query);
                });
        };

        $mapDashboardProgress = static fn (Progress $progress): array => [
            'id' => $progress->id,
            'details' => $progress->details,
            'date' => $progress->date?->toDateString(),
            'company' => [
                'id' => $progress->company->id,
                'name' => $progress->company->name,
            ],
            'contact' => $progress->contact
                ? [
                    'id' => $progress->contact->id,
                    'name' => $progress->contact->name,
                ]
                : null,
        ];

        $upcomingProgresses = $buildProgressQuery()
            ->whereDate('date', '>=', $today)
            ->whereDate('date', '<=', $nextWeek)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map($mapDashboardProgress)
            ->all();

        $pastDueProgresses = $buildProgressQuery()
            ->whereDate('date', '<', $today)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map($mapDashboardProgress)
            ->all();

        return Inertia::render('Dashboard', [
            'companies' => $dealerships,
            'upcomingProgresses' => $upcomingProgresses,
            'pastDueProgresses' => $pastDueProgresses,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'rating' => $request->input('rating', ''),
                'type' => $request->input('type', ''),
                'scope' => $scope,
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
            'progresses' => fn ($query) => $query
                ->with(['contact:id,name'])
                ->orderByRaw('completed_at is not null')
                ->orderByDesc('date')
                ->orderByDesc('created_at'),
        ]);

        return Inertia::render('Company/Show', [
            'company' => DealershipShowResource::make($company)->resolve(),
            'allUsers' => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user?->current_organization_id) {
            return back()->withErrors([
                'name' => 'Please select an organization before creating a company.',
            ]);
        }

        $company = Company::query()->create([
            'organization_id' => $user->current_organization_id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'status' => 'active',
            'rating' => 'warm',
            'type' => 'general',
        ]);

        return redirect()
            ->route('company.show', $company)
            ->with('success', 'Company created successfully.');
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
