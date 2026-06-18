<?php

use Illuminate\Contracts\Routing\ResponseFactory;
use Splitstack\InertiaSplit\Migration\Http\HybridResponseFactory;
use Splitstack\InertiaSplit\Split\Controllers\HybridController;

it('binds the split facade root as a singleton', function () {
    $resolved = app('split');

    expect($resolved)->toBeInstanceOf(HybridController::class);
});

it('resolves the same split instance on repeated resolution', function () {
    expect(app('split'))->toBe(app('split'));
});

it('does not bind HybridResponseFactory automatically', function () {
    expect(app(ResponseFactory::class))->not->toBeInstanceOf(HybridResponseFactory::class);
});
