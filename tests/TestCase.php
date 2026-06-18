<?php

namespace Splitstack\InertiaSplit\Tests;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Splitstack\InertiaSplit\InertiaSplitServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            InertiaSplitServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['view']->addLocation(__DIR__.'/views');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }
}
