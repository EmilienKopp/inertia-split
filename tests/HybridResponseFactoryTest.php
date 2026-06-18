<?php

use Illuminate\Contracts\Routing\ResponseFactory;
use Splitstack\InertiaSplit\Migration\Http\HybridResponseFactory;
use Splitstack\InertiaSplit\Tests\Fixtures\FakeController;
use Splitstack\InertiaSplit\Tests\Fixtures\FakeInvokableController;

beforeEach(function () {
    app()->singleton(ResponseFactory::class, HybridResponseFactory::class);

    app('router')->get('/users', [FakeController::class, 'index']);
    app('router')->get('/users/{id}', [FakeController::class, 'show']);
    app('router')->post('/users', [FakeController::class, 'store']);
    app('router')->get('/dashboard', [FakeController::class, 'dashboard']);
    app('router')->get('/settings', FakeInvokableController::class);
    app('router')->get('/closure', fn () => response()->json(['from' => 'closure']));
});

// ── Plain JSON fallback ────────────────────────────────────────────────────────

it('returns plain JSON when X-Inertia header is absent', function () {
    $response = $this->get('/users');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertJson(['users' => [['id' => 1, 'name' => 'Alice']]]);
    $response->assertHeaderMissing('X-Inertia');
});

it('returns plain JSON when controller method has no InertiaComponent attribute', function () {
    $response = $this->withHeader('X-Inertia', 'true')->post('/users');

    $response->assertStatus(201);
    $response->assertJson(['created' => true]);
    $response->assertHeaderMissing('X-Inertia');
});

it('preserves the status code for plain JSON fallback responses', function () {
    $response = $this->post('/users');

    $response->assertStatus(201);
});

it('returns plain JSON for closure routes even with X-Inertia header', function () {
    $response = $this->withHeader('X-Inertia', 'true')->get('/closure');

    $response->assertStatus(200);
    $response->assertJson(['from' => 'closure']);
    $response->assertHeaderMissing('X-Inertia');
});

it('detects InertiaComponent on __invoke for invokable controllers', function () {
    // Laravel stores invokable actions as "ClassName@__invoke", so the attribute IS resolved.
    $response = $this->withHeader('X-Inertia', 'true')->get('/settings');

    $response->assertStatus(200);
    $response->assertHeader('X-Inertia', 'true');
    $response->assertJson(['component' => 'Settings']);
});

it('falls back to JSON for invokable controllers without X-Inertia header', function () {
    $response = $this->get('/settings');

    $response->assertStatus(200);
    $response->assertJson(['settings' => []]);
    $response->assertHeaderMissing('X-Inertia');
});

// ── Inertia response ───────────────────────────────────────────────────────────

it('returns an Inertia response when X-Inertia header is present and attribute is found', function () {
    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertStatus(200);
    $response->assertHeader('X-Inertia', 'true');
});

it('includes the correct component name in the Inertia response', function () {
    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertJson(['component' => 'Users/Index']);
});

it('passes array data as props to the Inertia response', function () {
    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertJson(['props' => ['users' => [['id' => 1, 'name' => 'Alice']]]]);
});

it('converts Arrayable data to an array for Inertia props', function () {
    $response = $this->withHeader('X-Inertia', 'true')->get('/dashboard');

    $response->assertJson(['component' => 'Dashboard']);
    $response->assertJson(['props' => ['stats' => ['visits' => 42]]]);
});

it('returns the full Inertia page JSON envelope', function () {
    // assertInertia() checks viewData('page') and only works for non-X-Inertia full-page loads;
    // our factory exclusively returns Inertia responses on the X-Inertia path, so we assert
    // the JSON envelope shape directly.
    $response = $this->withHeader('X-Inertia', 'true')->get('/users');

    $response->assertJsonStructure(['component', 'props', 'url', 'version', 'clearHistory', 'encryptHistory']);
    $response->assertJson([
        'component' => 'Users/Index',
        'props'     => ['users' => [['id' => 1, 'name' => 'Alice']]],
    ]);
});

// ── Layout / rootView ──────────────────────────────────────────────────────────

it('returns a valid Inertia response when layout attribute is provided', function () {
    $response = $this->withHeader('X-Inertia', 'true')->get('/users/1');

    $response->assertStatus(200);
    $response->assertHeader('X-Inertia', 'true');
    $response->assertJson(['component' => 'Users/Show']);
});

it('sets rootView on the Inertia\\Response instance when layout is provided', function () {
    $capturedRootView = null;

    // Replace the Inertia ResponseFactory to capture the rootView set on each Response
    app()->extend(\Inertia\ResponseFactory::class, function ($factory) use (&$capturedRootView) {
        return new class($factory, $capturedRootView) extends \Inertia\ResponseFactory {
            public function __construct(
                private readonly \Inertia\ResponseFactory $inner,
                public ?string &$capturedRootView,
            ) {}

            public function render(string $component, $props = []): \Inertia\Response
            {
                $response = $this->inner->render($component, $props);

                return new class($response, $this->capturedRootView) extends \Inertia\Response {
                    public function __construct(
                        private readonly \Inertia\Response $wrapped,
                        public ?string &$capturedRootView,
                    ) {
                        // Intentionally do not call parent constructor — we delegate
                    }

                    public function rootView(string $rootView): static
                    {
                        $this->capturedRootView = $rootView;
                        $this->wrapped->rootView($rootView);
                        return $this;
                    }

                    public function toResponse($request): mixed
                    {
                        return $this->wrapped->toResponse($request);
                    }
                };
            }
        };
    });

    $this->withHeader('X-Inertia', 'true')->get('/users/1');

    expect($capturedRootView)->toBe('layouts.app');
});

it('does not set rootView when layout is null', function () {
    $rootViewCalled = false;

    app()->extend(\Inertia\ResponseFactory::class, function ($factory) use (&$rootViewCalled) {
        return new class($factory, $rootViewCalled) extends \Inertia\ResponseFactory {
            public function __construct(
                private readonly \Inertia\ResponseFactory $inner,
                public bool &$rootViewCalled,
            ) {}

            public function render(string $component, $props = []): \Inertia\Response
            {
                $response = $this->inner->render($component, $props);

                return new class($response, $this->rootViewCalled) extends \Inertia\Response {
                    public function __construct(
                        private readonly \Inertia\Response $wrapped,
                        public bool &$rootViewCalled,
                    ) {}

                    public function rootView(string $rootView): static
                    {
                        $this->rootViewCalled = true;
                        $this->wrapped->rootView($rootView);
                        return $this;
                    }

                    public function toResponse($request): mixed
                    {
                        return $this->wrapped->toResponse($request);
                    }
                };
            }
        };
    });

    $this->withHeader('X-Inertia', 'true')->get('/users');

    expect($rootViewCalled)->toBeFalse();
});
