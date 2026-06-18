<?php

namespace Splitstack\InertiaSplit;

use Illuminate\Support\ServiceProvider;
use Splitstack\InertiaSplit\Split\Controllers\HybridController;

class InertiaSplitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('split', HybridController::class);
    }
}
