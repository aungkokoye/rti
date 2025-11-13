<?php
declare(strict_types=1);

namespace app\Modules\Task\Providers;

use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register task application services.
     */
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Bootstrap task application services.
     */
    public function boot(): void
    {
        //
    }
}
