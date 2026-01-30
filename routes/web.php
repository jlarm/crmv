<?php

declare(strict_types=1);

use App\Http\Controllers\DealershipController;
use App\Http\Controllers\OrganizationController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DealershipController::class, 'index'])->name('dashboard');

    Route::post('organization/create', [OrganizationController::class, 'store'])
        ->middleware([HandlePrecognitiveRequests::class])
        ->name('organization.store');

    Route::put('organization/{organization}/switch', [OrganizationController::class, 'switch'])
        ->name('organization.switch');
});

Route::resource('dealerships', DealershipController::class)
    ->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
