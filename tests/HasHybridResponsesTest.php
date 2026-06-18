<?php

use Splitstack\InertiaSplit\Split\Concerns\HasHybridResponses;
use Splitstack\InertiaSplit\Split\Controllers\HybridController;
use Splitstack\InertiaSplit\Split\SplitResponseBuilder;

it('HybridController uses the HasHybridResponses trait', function () {
    expect(class_uses_recursive(HybridController::class))
        ->toContain(HasHybridResponses::class);
});

it('respond() returns a SplitResponseBuilder', function () {
    $controller = new class {
        use HasHybridResponses;
    };

    expect($controller->respond([]))->toBeInstanceOf(SplitResponseBuilder::class);
});

it('respond() seeds the builder with the given data', function () {
    $controller = new class {
        use HasHybridResponses;
    };

    $builder = $controller->respond(['key' => 'value']);

    // Drive the builder to JSON to observe the data was captured
    app('router')->get('/trait-test', fn () => $builder->component('Test'));

    $response = $this->withHeader('Accept', 'application/json')->get('/trait-test');

    $response->assertJson(['key' => 'value']);
});

it('respond() defaults to empty data when called with no arguments', function () {
    $controller = new class {
        use HasHybridResponses;
    };

    $builder = $controller->respond();

    app('router')->get('/empty-trait-test', fn () => $builder->component('Test'));

    $response = $this->withHeader('Accept', 'application/json')->get('/empty-trait-test');

    $response->assertExactJson([]);
});

it('each respond() call returns a fresh SplitResponseBuilder', function () {
    $controller = new class {
        use HasHybridResponses;
    };

    $a = $controller->respond(['x' => 1]);
    $b = $controller->respond(['y' => 2]);

    expect($a)->not->toBe($b);
});
