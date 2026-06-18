# inertia-split

`composer require splitstack/inertia-split`

Two independent tools for mixing Inertia and JSON responses in Laravel. Use one or both.

---

## Migrate an existing API to Inertia — without touching controller bodies

You have a working Laravel API. You want to adopt Inertia incrementally. The normal path means rewriting every `response()->json(...)` call — a lot of churn before anything works.

This package lets you annotate a method and leave the body alone. When an Inertia request comes in, `response()->json()` renders the component instead. When an API client calls the same endpoint, plain JSON comes back. The controller doesn't know the difference.

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

**Setup:** Register the factory override in your `AppServiceProvider`. This is an explicit opt-in — the package doesn't do it automatically.

```php
use Illuminate\Contracts\Routing\ResponseFactory;
use Splitstack\InertiaSplit\Migration\Http\HybridResponseFactory;

public function register(): void
{
    $this->app->singleton(ResponseFactory::class, HybridResponseFactory::class);
}
```

**How it works:** When `response()->json()` is called, `HybridResponseFactory` checks the current route action for `#[InertiaComponent]`. If the attribute is present and the request carries an `X-Inertia` header, it renders the component with your data as props. Otherwise it falls through to normal JSON. Methods without the attribute are completely unaffected.

---

## Build intentionally dual-mode endpoints

You're designing something new that deliberately serves both an Inertia SPA and an API consumer from the same action. Use `SplitResponseBuilder` for explicit, readable control — including Inertia redirects.

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

`->component()` renders an Inertia component for SPA requests and returns JSON for API requests. `->route()` issues an Inertia client-side redirect for SPA requests. You must chain exactly one — specifying both or neither throws.

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

## Requirements

|                           |              |
| ------------------------- | ------------ |
| PHP                       | ^8.2         |
| Laravel                   | 11 · 12 · 13 |
| inertiajs/inertia-laravel | ^2.0         |

The service provider and `Split` facade alias are auto-discovered.

## License

MIT
