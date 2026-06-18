<?php

namespace Splitstack\InertiaSplit\Tests\Fixtures;

use Illuminate\Contracts\Support\Arrayable;
use Splitstack\InertiaSplit\Migration\Attributes\InertiaComponent;

class FakeController
{
    #[InertiaComponent('Users/Index')]
    public function index(): mixed
    {
        return response()->json(['users' => [['id' => 1, 'name' => 'Alice']]]);
    }

    #[InertiaComponent('Users/Show', layout: 'layouts.app')]
    public function show(): mixed
    {
        return response()->json(['user' => ['id' => 1, 'name' => 'Alice']]);
    }

    public function store(): mixed
    {
        return response()->json(['created' => true], 201);
    }

    #[InertiaComponent('Dashboard')]
    public function dashboard(): mixed
    {
        return response()->json(new class implements Arrayable {
            public function toArray(): array
            {
                return ['stats' => ['visits' => 42]];
            }
        });
    }
}
