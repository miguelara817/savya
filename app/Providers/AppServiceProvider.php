<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User || ! request()->hasSession()) {
                return;
            }

            $session = UserSession::query()
                ->where('user_id', $event->user->getKey())
                ->whereNull('logged_out_at')
                ->orderByDesc('logged_in_at')
                ->first();

            $session?->update(['logged_out_at' => now()]);
        });

        Event::listen(CurrentDeviceLogout::class, function ($event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $event->user->userSessions()
                ->whereNull('logged_out_at')
                ->orderByDesc('logged_in_at')
                ->first()
                ?->update(['logged_out_at' => now()]);
        });
    }
}
