<?php

namespace App\Providers;

use App\Repositories\Contracts\TareaRepositoryInterface;
use App\Repositories\TareaRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TareaRepositoryInterface::class, TareaRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
