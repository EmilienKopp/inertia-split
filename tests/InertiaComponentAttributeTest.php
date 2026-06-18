<?php

use Splitstack\InertiaSplit\Migration\Attributes\InertiaComponent;

it('stores the component name', function () {
    $attr = new InertiaComponent('Users/Index');

    expect($attr->component)->toBe('Users/Index');
});

it('defaults layout to null', function () {
    $attr = new InertiaComponent('Users/Index');

    expect($attr->layout)->toBeNull();
});

it('stores an optional layout', function () {
    $attr = new InertiaComponent('Users/Index', layout: 'layouts.app');

    expect($attr->layout)->toBe('layouts.app');
});

it('is declared as a PHP attribute', function () {
    $reflection = new ReflectionClass(InertiaComponent::class);
    $attributes = $reflection->getAttributes(Attribute::class);

    expect($attributes)->not->toBeEmpty();
});

it('is only applicable to methods', function () {
    $reflection = new ReflectionClass(InertiaComponent::class);
    $attributeMeta = $reflection->getAttributes(Attribute::class)[0]->newInstance();

    expect($attributeMeta->flags & Attribute::TARGET_METHOD)->toBe(Attribute::TARGET_METHOD);
});

it('is not applicable to classes', function () {
    $reflection = new ReflectionClass(InertiaComponent::class);
    $attributeMeta = $reflection->getAttributes(Attribute::class)[0]->newInstance();

    expect($attributeMeta->flags & Attribute::TARGET_CLASS)->toBe(0);
});

it('component and layout properties are readonly', function () {
    $reflection = new ReflectionClass(InertiaComponent::class);

    expect($reflection->getProperty('component')->isReadOnly())->toBeTrue();
    expect($reflection->getProperty('layout')->isReadOnly())->toBeTrue();
});
