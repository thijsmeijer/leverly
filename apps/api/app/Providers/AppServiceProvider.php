<?php

namespace App\Providers;

use App\Console\Commands\SyncScribeOpenApiCommand;
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncScribeOpenApiCommand::class,
            ]);
        }
    }
}
