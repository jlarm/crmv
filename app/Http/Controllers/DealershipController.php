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
            ->withRating($request->input('rating'))
            ->sortBy($request->input('sort'), $request->input('direction'))
            ->select('id', 'name', 'city', 'state', 'status', 'rating')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($dealership) => DealershipResource::make($dealership)->resolve());

        return Inertia::render('Dashboard', [
            'dealerships' => $dealerships,
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
}
