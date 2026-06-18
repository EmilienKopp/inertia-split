# inertia-split

`composer require splitstack/inertia-split`

Serve Inertia and JSON from the same Laravel controller — no branching logic, no duplicate routes, no separate API layer.

- [inertia-split](#inertia-split)
  - [Hybrid responses](#hybrid-responses)
  - [Incremental migration from an existing API](#incremental-migration-from-an-existing-api)
  - [Requirements](#requirements)
  - [License](#license)

---

## Hybrid responses

Design endpoints that deliberately serve both an Inertia SPA and an API consumer from the same action. `SplitResponseBuilder` handles the branching so your controller doesn't have to.

```php
use Splitstack\InertiaSplit\Split\Concerns\HasHybridResponses;

class ProductController extends Controller
{
    use HasHybridResponses;

    public function index()
    {
        return $this->respond(Product::paginate())
            ->component('Products/Index');
    }

    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());

        return $this->respond()->route('products.index');
    }
}
```

`->component()` renders an Inertia component for SPA requests and returns JSON for API requests. `->route()` issues an Inertia client-side redirect for SPA requests. Chain exactly one — specifying both or neither throws.

**Alternatives to the trait:**

Extend `HybridController` to skip the `use` declaration:

```php
use Splitstack\InertiaSplit\Split\Controllers\HybridController;

class ProductController extends HybridController { ... }
```

Or use the `Split` facade from anywhere:

```php
use Split;

return Split::respond($data)->component('Products/Index');
```

---

## Incremental migration from an existing API

Already have a working Laravel API and want to adopt Inertia without a rewrite? Annotate a method and leave the body alone. When an Inertia request comes in, `response()->json()` renders the component instead. When an API client hits the same endpoint, it gets plain JSON back. The controller doesn't know the difference.

```php
use Splitstack\InertiaSplit\Migration\Attributes\InertiaComponent;

class UserController extends Controller
{
    #[InertiaComponent('Users/Index')]
    public function index()
    {
        return response()->json(User::paginate()); // untouched
    }

    #[InertiaComponent('Users/Show', layout: 'admin')]
    public function show(User $user)
    {
        return response()->json($user); // untouched
    }

    public function store(Request $request) // no attribute = always JSON
    {
        return response()->json(User::create($request->validated()), 201);
    }
}
```

Annotate methods as you build out the frontend. Everything else keeps working.

**Setup:** Register the factory override in your `AppServiceProvider`. Explicit opt-in — nothing changes until you do this.

```php
use Illuminate\Contracts\Routing\ResponseFactory;
use Splitstack\InertiaSplit\Migration\Http\HybridResponseFactory;

public function register(): void
{
    $this->app->singleton(ResponseFactory::class, HybridResponseFactory::class);
}
```

**How it works:** `HybridResponseFactory` checks the current route action for `#[InertiaComponent]`. If the attribute is present and the request carries an `X-Inertia` header, it renders the component with your data as props. Otherwise it falls through to normal JSON. Methods without the attribute are completely unaffected.

---

## Requirements

|                           |              |
| ------------------------- | ------------ |
| PHP                       | ^8.2         |
| Laravel                   | 11 · 12 · 13 |
| inertiajs/inertia-laravel | ^2.0         |

## License

MIT
