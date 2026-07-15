<?php

namespace Splitstack\InertiaSplit\Migration\Http;

use Illuminate\Routing\ResponseFactory;
use Inertia\Inertia;
use ReflectionMethod;
use Splitstack\InertiaSplit\Migration\Attributes\InertiaComponent;

class HybridResponseFactory extends ResponseFactory
{
    public function json($data = [], $status = 200, array $headers = [], $options = 0): \Illuminate\Http\JsonResponse|\Inertia\Response
    {
        $component = $this->resolveInertiaComponent();

        if ($component && request()->header('X-Inertia')) {
            $response = Inertia::render($component->component, is_array($data) ? $data : $data->toArray());

            if ($component->layout) {
                $response->rootView($component->layout);
            }

            return $response;
        }

        return parent::json($data, $status, $headers, $options);
    }

    private function resolveInertiaComponent(): ?InertiaComponent
    {
        $action = app('router')->current()?->getAction('uses');

        if (! is_string($action) || ! str_contains($action, '@')) {
            return null;
        }

        [$class, $method] = explode('@', $action);

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return null;
        }

        $attr = (new ReflectionMethod($class, $method))
            ->getAttributes(InertiaComponent::class)[0] ?? null;

        return $attr?->newInstance();
    }
}
