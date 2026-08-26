<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Services\SettingsService;

class MailSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only apply settings if database is accessible and settings table exists
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            // Apply mail settings from database
            $this->applyMailSettings();
        } catch (\Exception $e) {
            // Silently fail if settings can't be loaded (e.g., during install)
        }
    }

    /**
     * Apply mail settings from database to config.
     */
    protected function applyMailSettings(): void
    {
        // Default mailer
        if (SettingsService::has('mail_mailer')) {
            Config::set('mail.default', SettingsService::get('mail_mailer'));
        }

        // SMTP settings
        if (SettingsService::has('mail_host')) {
            Config::set('mail.mailers.smtp.host', SettingsService::get('mail_host'));
        }
        if (SettingsService::has('mail_port')) {
            Config::set('mail.mailers.smtp.port', SettingsService::get('mail_port'));
        }
        if (SettingsService::has('mail_username')) {
            Config::set('mail.mailers.smtp.username', SettingsService::get('mail_username'));
        }
        if (SettingsService::has('mail_password')) {
            Config::set('mail.mailers.smtp.password', SettingsService::get('mail_password'));
        }
        if (SettingsService::has('mail_encryption')) {
            Config::set('mail.mailers.smtp.encryption', SettingsService::get('mail_encryption'));
        }
        if (SettingsService::has('mail_from_address')) {
            Config::set('mail.from.address', SettingsService::get('mail_from_address'));
        }
        if (SettingsService::has('mail_from_name')) {
            Config::set('mail.from.name', SettingsService::get('mail_from_name'));
        }
    }
}
