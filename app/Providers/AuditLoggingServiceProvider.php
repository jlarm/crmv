<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AuditLoggingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}

    protected function logRequests(): void
    {
        app('router')->after(function ($request, $response) {
            if (app()->runningInConsole()) {
                return;
            }

            activity('request')
                ->causedBy(Auth::user())
                ->withProperties([
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'status' => $response->status(),
                ])
                ->log('Request handled');
        });
    }

    protected function logAuthEvents(): void
    {
        Event::listen(Login::class, static function (Login $event) {
            activity('auth')
                ->causedBy($event->user)
                ->log('User logged in');
        });

        Event::listen(Logout::class, static function (Logout $event) {
            activity('auth')
                ->causedBy($event->user)
                ->log('User logged out');
        });

        Event::listen(PasswordReset::class, static function (PasswordReset $event) {
            activity('auth')
                ->causedBy($event->user)
                ->log('Password reset');
        });
    }
}
