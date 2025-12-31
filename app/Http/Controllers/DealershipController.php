<?php

namespace App\Http\Controllers;

use App\Http\Requests\DealershipIndexRequest;
use App\Http\Resources\DealershipResource;
use App\Models\Dealership;
use Inertia\Inertia;
use Inertia\Response;

class DealershipController extends Controller
{
    public function index(DealershipIndexRequest $request): Response
    {
        $dealerships = Dealership::query()
            ->whereNot('status', 'imported')
            ->search($request->input('search'))
            ->withStatus($request->input('status'))
            ->sortBy($request->input('sort'), $request->input('direction'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($dealership) => DealershipResource::make($dealership)->resolve());

        return Inertia::render('Dashboard', [
            'dealerships' => $dealerships,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'filterOptions' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
            ],
        ]);
    }
}
