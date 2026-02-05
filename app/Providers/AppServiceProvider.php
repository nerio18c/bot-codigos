<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

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
        $tz = env('APP_TIMEZONE', 'America/Lima');
        date_default_timezone_set($tz);
        Carbon::setLocale(config('app.locale', 'es'));
    }
}
