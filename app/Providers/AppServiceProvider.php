<?php

namespace App\Providers;

use App\Services\SettingService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton agar cache peta setting dibagi satu instance (facade Settings).
        $this->app->singleton(SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->overrideMailConfig();

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

    /**
     * Timpa `config('mail.*')` dari `settings` SMTP (ARCHITECTURE §2) sehingga
     * kredensial dikelola admin, bukan hardcode env. Hanya aktif bila tabel
     * settings sudah ada & host terisi (aman saat migrate:fresh / SMTP kosong).
     */
    private function overrideMailConfig(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $settings = $this->app->make(SettingService::class);

        if (blank($settings->get('mail_host'))) {
            return;
        }

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->get('mail_host'),
            'mail.mailers.smtp.port' => $settings->get('mail_port') ?? config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username' => $settings->get('mail_username'),
            'mail.mailers.smtp.password' => $settings->get('mail_password'),
            'mail.from.address' => $settings->get('mail_from_address') ?: config('mail.from.address'),
            'mail.from.name' => $settings->get('mail_from_name') ?: config('mail.from.name'),
        ]);
    }
}
