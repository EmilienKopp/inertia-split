<?php

declare(strict_types=1);

namespace Splitstack\InertiaSplit\Split;

use Illuminate\Contracts\Support\Responsable;
use InvalidArgumentException;

final class SplitResponseBuilder implements Responsable
{
    private array $data = [];

    private ?string $component = null;

    private ?string $route = null;

    private array $params = [];

    public function respond(iterable $data): self
    {
        $this->data = is_array($data) ? $data : iterator_to_array($data);

        return $this;
    }

    public function component(?string $component): self
    {
        $this->component = $component;

        return $this;
    }

    public function route(?string $route, array $params = []): self
    {
        $this->route = $route;
        $this->params = $params;

        return $this;
    }

    public function toResponse($request)
    {
        if ($this->route && $this->component) {
            throw new InvalidArgumentException('Cannot specify both a component and a route.');
        }

        if (! $this->route && ! $this->component) {
            throw new InvalidArgumentException('Must specify either a component or a route.');
        }

        if ($request->wantsJson() && ! $request->hasHeader('X-Inertia')) {
            return response()->json($this->data);
        }

        if ($this->component) {
            return inertia($this->component, $this->data)->toResponse($request);
        }

        return inertia()->location(route($this->route, $this->params));
    }
}
