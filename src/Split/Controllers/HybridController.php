<?php

declare(strict_types=1);

namespace Splitstack\InertiaSplit\Split\Controllers;

use Illuminate\Routing\Controller;
use Splitstack\InertiaSplit\Split\Concerns\HasHybridResponses;

class HybridController extends Controller
{
    use HasHybridResponses;
}
