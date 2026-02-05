<?php

declare(strict_types=1);

use App\Http\Controllers\CompanyContactController;
use App\Http\Controllers\CompanyStoreController;
use App\Http\Controllers\CompanyUserController;
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

    Route::post('companies/{company}/stores', [CompanyStoreController::class, 'store'])
        ->name('companies.stores.store');
    Route::put('companies/{company}/stores/{store}', [CompanyStoreController::class, 'update'])
        ->name('companies.stores.update');
    Route::delete('companies/{company}/stores/{store}', [CompanyStoreController::class, 'destroy'])
        ->name('companies.stores.destroy');

    Route::post('companies/{company}/contacts', [CompanyContactController::class, 'store'])
        ->name('companies.contacts.store');
    Route::put('companies/{company}/contacts/{contact}', [CompanyContactController::class, 'update'])
        ->name('companies.contacts.update');
    Route::delete('companies/{company}/contacts/{contact}', [CompanyContactController::class, 'destroy'])
        ->name('companies.contacts.destroy');

    Route::put('companies/{company}/users', [CompanyUserController::class, 'update'])
        ->name('companies.users.update');
});

Route::resource('company', DealershipController::class)
    ->only(['show', 'update'])
    ->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
