<?php

namespace Splitstack\InertiaSplit\Tests\Fixtures;

use Splitstack\InertiaSplit\Migration\Attributes\InertiaComponent;

class FakeInvokableController
{
    #[InertiaComponent('Settings')]
    public function __invoke(): mixed
    {
        return response()->json(['settings' => []]);
    }
}
