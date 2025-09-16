<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\DealershipResource;
use App\Models\Dealership;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'dealerships' => DealershipResource::collection(
                Dealership::select(['id', 'name', 'city', 'state', 'phone', 'status', 'rating'])->get()
            ),
        ]);
    }
}
