<?php

declare(strict_types=1);

namespace Splitstack\InertiaSplit\Split\Facades;

use Illuminate\Support\Facades\Facade;
use Splitstack\InertiaSplit\Split\SplitResponseBuilder;

/**
 * @method static SplitResponseBuilder respond(iterable $data = [])
 */
class Split extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'split';
    }
}
