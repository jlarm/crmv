<?php

declare(strict_types=1);

use App\Http\Controllers\CompanyContactController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyProgressController;
use App\Http\Controllers\CompanyStoreController;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\UserManagementController;
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
    Route::get('/dashboard', [CompanyController::class, 'index'])->name('dashboard');

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

    Route::post('companies/{company}/progresses', [CompanyProgressController::class, 'store'])
        ->name('companies.progresses.store');
    Route::put('companies/{company}/progresses/{progress}', [CompanyProgressController::class, 'update'])
        ->name('companies.progresses.update');
    Route::delete('companies/{company}/progresses/{progress}', [CompanyProgressController::class, 'destroy'])
        ->name('companies.progresses.destroy');

    Route::post('companies/{company}/contacts', [CompanyContactController::class, 'store'])
        ->name('companies.contacts.store');
    Route::put('companies/{company}/contacts/{contact}', [CompanyContactController::class, 'update'])
        ->name('companies.contacts.update');
    Route::delete('companies/{company}/contacts/{contact}', [CompanyContactController::class, 'destroy'])
        ->name('companies.contacts.destroy');

    Route::put('companies/{company}/users', [CompanyUserController::class, 'update'])
        ->name('companies.users.update');

    Route::get('/users', [UserManagementController::class, 'index'])
        ->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->name('users.store');
    Route::put('/users/{user}/organizations', [UserManagementController::class, 'updateOrganizations'])
        ->name('users.organizations.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
        ->name('users.destroy');
});

Route::resource('company', CompanyController::class)
    ->only(['show', 'store', 'update'])
    ->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
