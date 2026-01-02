<?php

declare(strict_types=1);

use App\Http\Controllers\DealershipController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DealershipController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::resource('dealerships', DealershipController::class)
    ->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
