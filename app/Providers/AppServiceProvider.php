<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Channels\TwilioChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\CompteService::class, function ($app) {
            return new \App\Services\CompteService();
        });
    }

    /**
     * Bootstrap any application services.
     */
   public function boot()
{
    Notification::extend('twilio', function ($app) {
        return new TwilioChannel();
    });
}
}
