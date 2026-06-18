<?php

namespace Splitstack\InertiaSplit\Migration\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class InertiaComponent
{
    public function __construct(
        public readonly string $component,
        public readonly ?string $layout = null,
    ) {}
}
