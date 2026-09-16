<?php

namespace App\Providers;

use App\Services\ClassPositionService;
use App\Services\PromotionEvaluator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the promotion evaluator as a singleton
        $this->app->singleton(PromotionEvaluator::class, function ($app) {
            return new PromotionEvaluator();
        });

        // Register the class position service as a singleton
        $this->app->singleton(ClassPositionService::class, function ($app) {
            return new ClassPositionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
