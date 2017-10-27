<?php

namespace BeauB\EasypostWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EasypostWebhooksServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/easypost-webhooks.php' => config_path('easypost-webhooks.php'),
            ], 'config');
        }

        if (! class_exists('CreateEasypostWebhookCallsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_easypost_webhook_calls_table.php.stub' => database_path('migrations/'.$timestamp.'_create_easypost_webhook_calls_table.php'),
            ], 'migrations');
        }

        Route::macro('EasypostWebhooks', function ($url) {
            return Route::post($url, '\BeauB\EasypostWebhooks\EasypostWebhooksController');
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/easypost-webhooks.php', 'easypost-webhooks');
    }
}
