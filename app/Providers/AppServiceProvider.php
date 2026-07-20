<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
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
        // Audit trail login/logout (PRD §7.1, UX_SPEC 1.A.1 langkah 5) via ActivityLog.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof Model) {
                activity()->causedBy($event->user)->event('login')->log('login');
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof Model) {
                activity()->causedBy($event->user)->event('logout')->log('logout');
            }
        });
    }
}
