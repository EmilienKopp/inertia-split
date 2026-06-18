<?php

use Splitstack\InertiaSplit\Split\Facades\Split;
use Splitstack\InertiaSplit\Split\SplitResponseBuilder;

it('Split facade resolves from the container', function () {
    expect(app('split'))->toBeInstanceOf(\Illuminate\Routing\Controller::class);
});

it('Split::respond() returns a SplitResponseBuilder', function () {
    expect(Split::respond([]))->toBeInstanceOf(SplitResponseBuilder::class);
});

it('Split facade serves JSON for wantsJson requests', function () {
    app('router')->get('/facade-users', function () {
        return Split::respond(['users' => []])->component('Users/Index');
    });

    $response = $this->withHeader('Accept', 'application/json')->get('/facade-users');

    $response->assertStatus(200);
    $response->assertJson(['users' => []]);
    $response->assertHeaderMissing('X-Inertia');
});

it('Split facade serves Inertia for X-Inertia requests', function () {
    app('router')->get('/facade-users', function () {
        return Split::respond(['users' => [['id' => 1]]])->component('Users/Index');
    });

    $response = $this->withHeader('X-Inertia', 'true')->get('/facade-users');

    $response->assertStatus(200);
    $response->assertHeader('X-Inertia', 'true');
    $response->assertJson(['component' => 'Users/Index', 'props' => ['users' => [['id' => 1]]]]);
});
