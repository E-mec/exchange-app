<?php

namespace Modules\Auth\tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load module migrations
//        $this->loadMigrationsFrom(base_path('Modules/Auth/database/migrations'));

        // Load module API routes
        $this->loadModuleRoutes();
    }

    protected function loadModuleRoutes(): void
    {
        $apiRoutes = base_path('Modules/Auth/routes/api.php');

        if (file_exists($apiRoutes)) {
            $this->app['router']->group([
                'prefix' => 'api',
                'middleware' => 'api',
            ], function () use ($apiRoutes) {
                require $apiRoutes;
            });
        }
    }
}
