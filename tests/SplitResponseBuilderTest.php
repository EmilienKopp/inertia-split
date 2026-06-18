<?php

use Illuminate\Http\JsonResponse;
use Splitstack\InertiaSplit\Split\SplitResponseBuilder;

// ── Construction ───────────────────────────────────────────────────────────────

it('implements Responsable', function () {
    expect(new SplitResponseBuilder)
        ->toBeInstanceOf(\Illuminate\Contracts\Support\Responsable::class);
});

it('returns self from respond() for chaining', function () {
    $builder = new SplitResponseBuilder;
    expect($builder->respond([]))->toBe($builder);
});

it('returns self from component() for chaining', function () {
    $builder = new SplitResponseBuilder;
    expect($builder->component('Users/Index'))->toBe($builder);
});

it('returns self from route() for chaining', function () {
    $builder = new SplitResponseBuilder;
    expect($builder->route('home'))->toBe($builder);
});

// ── JSON API path ──────────────────────────────────────────────────────────────

it('returns plain JSON when request wantsJson and no X-Inertia header', function () {
    app('router')->get('/users', function () {
        return (new SplitResponseBuilder)
            ->respond(['users' => []])
            ->component('Users/Index');
    });

    $response = $this->withHeader('Accept', 'application/json')->get('/users');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertJson(['users' => []]);
    $response->assertHeaderMissing('X-Inertia');
});

it('passes all respond() data into the plain JSON response', function () {
    app('router')->get('/items', function () {
        return (new SplitResponseBuilder)
            ->respond(['items' => [1, 2, 3], 'total' => 3])
            ->component('Items/Index');
    });

    $response = $this->withHeader('Accept', 'application/json')->get('/items');

    $response->assertJson(['items' => [1, 2, 3], 'total' => 3]);
});

it('accepts an iterable (non-array) from respond()', function () {
    app('router')->get('/gen', function () {
        $gen = (function () {
            yield 'a' => 1;
            yield 'b' => 2;
        })();

        return (new SplitResponseBuilder)->respond($gen)->component('Gen');
    });

    $response = $this->withHeader('Accept', 'application/json')->get('/gen');

    $response->assertJson(['a' => 1, 'b' => 2]);
});

// ── Inertia path ───────────────────────────────────────────────────────────────

it('returns an Inertia response when X-Inertia header is present', function () {
    app('router')->get('/users', function () {
        return (new SplitResponseBuilder)
            ->respond(['users' => []])
            ->component('Users/Index');
    });

    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertStatus(200);
    $response->assertHeader('X-Inertia', 'true');
    $response->assertJson(['component' => 'Users/Index']);
});

it('passes respond() data as Inertia props', function () {
    app('router')->get('/users', function () {
        return (new SplitResponseBuilder)
            ->respond(['users' => [['id' => 1]]])
            ->component('Users/Index');
    });

    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertJson(['props' => ['users' => [['id' => 1]]]]);
});

it('returns an Inertia response without Accept:json when X-Inertia header is present', function () {
    app('router')->get('/users', function () {
        return (new SplitResponseBuilder)
            ->respond(['count' => 5])
            ->component('Users/Index');
    });

    // No Accept: application/json — simulates browser Inertia navigation
    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertHeader('X-Inertia', 'true');
    $response->assertJson(['component' => 'Users/Index', 'props' => ['count' => 5]]);
});

// ── Route redirect path ────────────────────────────────────────────────────────

it('returns an Inertia location redirect when route() is used and X-Inertia is present', function () {
    app('router')->get('/home', fn () => 'home')->name('home');
    app('router')->get('/redirect', function () {
        return (new SplitResponseBuilder)->respond([])->route('home');
    });

    $response = $this->withHeader('X-Inertia', 'true')->get('/redirect');

    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location');
});

// ── Validation errors ──────────────────────────────────────────────────────────

it('throws when both component and route are specified', function () {
    app('router')->get('/bad', function () {
        return (new SplitResponseBuilder)
            ->respond([])
            ->component('Users/Index')
            ->route('home');
    });
    app('router')->get('/home', fn () => 'home')->name('home');

    $this->withoutExceptionHandling()
         ->withHeader('X-Inertia', 'true')
         ->get('/bad');
})->throws(\InvalidArgumentException::class, 'Cannot specify both a component and a route.');

it('throws when neither component nor route is specified', function () {
    app('router')->get('/empty', function () {
        return (new SplitResponseBuilder)->respond([])->toResponse(request());
    });

    $this->withoutExceptionHandling()->get('/empty');
})->throws(\InvalidArgumentException::class, 'Must specify either a component or a route.');
