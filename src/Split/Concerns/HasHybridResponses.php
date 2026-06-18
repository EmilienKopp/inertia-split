<?php

declare(strict_types=1);

namespace Splitstack\InertiaSplit\Split\Concerns;

use Splitstack\InertiaSplit\Split\SplitResponseBuilder;

trait HasHybridResponses
{
    public function respond(iterable $data = []): SplitResponseBuilder
    {
        return (new SplitResponseBuilder())->respond($data);
    }
}
