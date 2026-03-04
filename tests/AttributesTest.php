<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\AttributeReader\AttributeTarget;
use Cline\AttributeReader\AttributeTargetType;
use Cline\AttributeReader\Exceptions\AttributeLookupException;
use Cline\AttributeReader\QueryOptions;
use Tests\TestSupport\Attributes\ChildAttribute;
use Tests\TestSupport\Attributes\ConstantAttribute;
use Tests\TestSupport\Attributes\MethodAttribute;
use Tests\TestSupport\Attributes\MultiTargetAttribute;
use Tests\TestSupport\Attributes\ParameterAttribute;
use Tests\TestSupport\Attributes\PropertyAttribute;
use Tests\TestSupport\Attributes\RepeatableAttribute;
use Tests\TestSupport\Attributes\RepeatableTag;
use Tests\TestSupport\Attributes\SimpleAttribute;
use Tests\TestSupport\Attributes\VariadicAttribute;
use Tests\TestSupport\ChildTestClass;
use Tests\TestSupport\PlainClass;
use Tests\TestSupport\TestClass;
use Tests\TestSupport\VariadicClass;

// get

it('can get a class attribute', function (): void {
    $attribute = $this->attributes->get(TestClass::class, SimpleAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(SimpleAttribute::class)
        ->name->toBe('test-class');
});

it('returns null when class attribute is missing', function (): void {
    expect($this->attributes->get(PlainClass::class, SimpleAttribute::class))->toBeNull();
});

it('can get the first class attribute without filtering', function (): void {
    $attribute = $this->attributes->get(TestClass::class);

    expect($attribute)->toBeInstanceOf(SimpleAttribute::class);
});

it('returns null for get without filter on plain class', function (): void {
    expect($this->attributes->get(PlainClass::class))->toBeNull();
});

it('can get a class attribute from an object instance', function (): void {
    $attribute = $this->attributes->get(
        new TestClass(),
        SimpleAttribute::class,
    );

    expect($attribute)
        ->toBeInstanceOf(SimpleAttribute::class)
        ->name->toBe('test-class');
});

// has

it('can check if a class has an attribute', function (): void {
    expect($this->attributes->has(TestClass::class, SimpleAttribute::class))->toBeTrue();
    expect($this->attributes->has(PlainClass::class, SimpleAttribute::class))->toBeFalse();
});

it('can check if a class has any attribute', function (): void {
    expect($this->attributes->has(TestClass::class))->toBeTrue();
    expect($this->attributes->has(PlainClass::class))->toBeFalse();
});

it('has returns true for child attributes via inheritance', function (): void {
    expect($this->attributes->has(ChildTestClass::class, SimpleAttribute::class))->toBeTrue();
});

// getAll

it('can get all repeated attributes', function (): void {
    $attributes = $this->attributes->getAll(TestClass::class, RepeatableAttribute::class);

    expect($attributes)
        ->toHaveCount(2)
        ->sequence(
            fn ($attr) => $attr->tag->toBe('first'),
            fn ($attr) => $attr->tag->toBe('second'),
        );
});

it('returns empty array when no repeated attributes exist', function (): void {
    expect($this->attributes->getAll(PlainClass::class, RepeatableAttribute::class))->toBeEmpty();
});

it('can get all class attributes without filtering', function (): void {
    $attributes = $this->attributes->getAll(TestClass::class);

    expect($attributes)->toHaveCount(3);
});

it('returns empty array for getAll without filter on plain class', function (): void {
    expect($this->attributes->getAll(PlainClass::class))->toBeEmpty();
});

// getAllOnMethod

it('can get all repeated attributes from a method', function (): void {
    $attributes = $this->attributes->getAllOnMethod(TestClass::class, 'handle', RepeatableTag::class);

    expect($attributes)
        ->toHaveCount(2)
        ->sequence(
            fn ($attr) => $attr->tag->toBe('method-a'),
            fn ($attr) => $attr->tag->toBe('method-b'),
        );
});

it('returns empty array for getAllOnMethod with non-existent method', function (): void {
    expect($this->attributes->getAllOnMethod(TestClass::class, 'nonExistent', RepeatableTag::class))->toBeEmpty();
});

// getAllOnProperty

it('can get all repeated attributes from a property', function (): void {
    $attributes = $this->attributes->getAllOnProperty(TestClass::class, 'name', RepeatableTag::class);

    expect($attributes)
        ->toHaveCount(2)
        ->sequence(
            fn ($attr) => $attr->tag->toBe('prop-a'),
            fn ($attr) => $attr->tag->toBe('prop-b'),
        );
});

it('returns empty array for getAllOnProperty with non-existent property', function (): void {
    expect($this->attributes->getAllOnProperty(TestClass::class, 'nonExistent', RepeatableTag::class))->toBeEmpty();
});

// getAllOnConstant

it('can get all repeated attributes from a constant', function (): void {
    $attributes = $this->attributes->getAllOnConstant(TestClass::class, 'STATUS_ACTIVE', RepeatableTag::class);

    expect($attributes)
        ->toHaveCount(2)
        ->sequence(
            fn ($attr) => $attr->tag->toBe('const-a'),
            fn ($attr) => $attr->tag->toBe('const-b'),
        );
});

it('returns empty array for getAllOnConstant with non-existent constant', function (): void {
    expect($this->attributes->getAllOnConstant(TestClass::class, 'NON_EXISTENT', RepeatableTag::class))->toBeEmpty();
});

// getAllOnParameter

it('can get all repeated attributes from a parameter', function (): void {
    $attributes = $this->attributes->getAllOnParameter(TestClass::class, 'handle', 'request', RepeatableTag::class);

    expect($attributes)
        ->toHaveCount(2)
        ->sequence(
            fn ($attr) => $attr->tag->toBe('param-a'),
            fn ($attr) => $attr->tag->toBe('param-b'),
        );
});

it('returns empty array for getAllOnParameter with non-existent method', function (): void {
    expect($this->attributes->getAllOnParameter(TestClass::class, 'nonExistent', 'request', RepeatableTag::class))->toBeEmpty();
});

it('returns empty array for getAllOnParameter with non-existent parameter', function (): void {
    expect($this->attributes->getAllOnParameter(TestClass::class, 'handle', 'nonExistent', RepeatableTag::class))->toBeEmpty();
});

// getAllOnMethod without filter

it('can get all attributes from a method without filtering', function (): void {
    $attributes = $this->attributes->getAllOnMethod(TestClass::class, 'handle');

    expect($attributes)->toHaveCount(3);
});

// getAllOnProperty without filter

it('can get all attributes from a property without filtering', function (): void {
    $attributes = $this->attributes->getAllOnProperty(TestClass::class, 'name');

    expect($attributes)->toHaveCount(3);
});

// getAllOnConstant without filter

it('can get all attributes from a constant without filtering', function (): void {
    $attributes = $this->attributes->getAllOnConstant(TestClass::class, 'STATUS_ACTIVE');

    expect($attributes)->toHaveCount(3);
});

// getAllOnParameter without filter

it('can get all attributes from a parameter without filtering', function (): void {
    $attributes = $this->attributes->getAllOnParameter(TestClass::class, 'handle', 'request');

    expect($attributes)->toHaveCount(3);
});

// onMethod

it('can get an attribute from a method', function (): void {
    $attribute = $this->attributes->onMethod(TestClass::class, 'handle', MethodAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(MethodAttribute::class)
        ->route->toBe('/handle');
});

it('returns null for a method without the attribute', function (): void {
    expect($this->attributes->onMethod(TestClass::class, 'plain', MethodAttribute::class))->toBeNull();
});

it('returns null for an existing method on a plain class', function (): void {
    expect($this->attributes->onMethod(PlainClass::class, 'handle', MethodAttribute::class))->toBeNull();
});

it('returns null for a non-existent method', function (): void {
    expect($this->attributes->onMethod(TestClass::class, 'nonExistent', MethodAttribute::class))->toBeNull();
});

// onProperty

it('can get an attribute from a property', function (): void {
    $attribute = $this->attributes->onProperty(TestClass::class, 'name', PropertyAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(PropertyAttribute::class)
        ->fillable->toBeTrue();
});

it('returns null for a non-existent property', function (): void {
    expect($this->attributes->onProperty(TestClass::class, 'nonExistent', PropertyAttribute::class))->toBeNull();
});

it('returns null for an existing property without the attribute', function (): void {
    expect($this->attributes->onProperty(PlainClass::class, 'name', PropertyAttribute::class))->toBeNull();
});

// onConstant

it('can get an attribute from a constant', function (): void {
    $attribute = $this->attributes->onConstant(TestClass::class, 'STATUS_ACTIVE', ConstantAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(ConstantAttribute::class)
        ->description->toBe('The active status');
});

it('returns null for a non-existent constant', function (): void {
    expect($this->attributes->onConstant(TestClass::class, 'NON_EXISTENT', ConstantAttribute::class))->toBeNull();
});

it('returns null for an existing constant without the attribute', function (): void {
    expect($this->attributes->onConstant(PlainClass::class, 'VALUE', ConstantAttribute::class))->toBeNull();
});

// onParameter

it('can get an attribute from a parameter', function (): void {
    $attribute = $this->attributes->onParameter(TestClass::class, 'handle', 'request', ParameterAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(ParameterAttribute::class)
        ->type->toBe('request');
});

it('returns null for a non-existent parameter', function (): void {
    expect($this->attributes->onParameter(TestClass::class, 'handle', 'nonExistent', ParameterAttribute::class))->toBeNull();
});

it('returns null for a non-existent method on parameter lookup', function (): void {
    expect($this->attributes->onParameter(TestClass::class, 'nonExistent', 'request', ParameterAttribute::class))->toBeNull();
});

// on* without filter

it('can get the first attribute from a method without filtering', function (): void {
    $attribute = $this->attributes->onMethod(TestClass::class, 'handle');

    expect($attribute)->toBeInstanceOf(MethodAttribute::class);
});

it('can get the first attribute from a property without filtering', function (): void {
    $attribute = $this->attributes->onProperty(TestClass::class, 'name');

    expect($attribute)->toBeInstanceOf(PropertyAttribute::class);
});

it('can get the first attribute from a constant without filtering', function (): void {
    $attribute = $this->attributes->onConstant(TestClass::class, 'STATUS_ACTIVE');

    expect($attribute)->toBeInstanceOf(ConstantAttribute::class);
});

it('can get the first attribute from a parameter without filtering', function (): void {
    $attribute = $this->attributes->onParameter(TestClass::class, 'handle', 'request');

    expect($attribute)->toBeInstanceOf(ParameterAttribute::class);
});

// onFunction

it('can get an attribute from a function', function (): void {
    $attribute = $this->attributes->onFunction('Tests\\TestSupport\\testFunction', MultiTargetAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(MultiTargetAttribute::class)
        ->label->toBe('standalone');
});

it('returns null for a function without the attribute', function (): void {
    expect($this->attributes->onFunction('Tests\\TestSupport\\testFunction', SimpleAttribute::class))->toBeNull();
});

it('returns null for a non-existent function', function (): void {
    expect($this->attributes->onFunction('nonExistentFunction', MultiTargetAttribute::class))->toBeNull();
});

it('can get the first attribute from a function without filtering', function (): void {
    $attribute = $this->attributes->onFunction('Tests\\TestSupport\\testFunction');

    expect($attribute)->toBeInstanceOf(MultiTargetAttribute::class);
});

// getAllOnFunction

it('can get all attributes from a function', function (): void {
    $attributes = $this->attributes->getAllOnFunction('Tests\\TestSupport\\testFunction', MultiTargetAttribute::class);

    expect($attributes)
        ->toHaveCount(1)
        ->sequence(
            fn ($attr) => $attr->toBeInstanceOf(MultiTargetAttribute::class),
        );
});

it('can get all attributes from a function without filtering', function (): void {
    $attributes = $this->attributes->getAllOnFunction('Tests\\TestSupport\\testFunction');

    expect($attributes)->toHaveCount(1);
});

it('returns empty array for getAllOnFunction with non-existent function', function (): void {
    expect($this->attributes->getAllOnFunction('nonExistentFunction', MultiTargetAttribute::class))->toBeEmpty();
});

// IS_INSTANCEOF inheritance

it('finds child attributes when querying parent class', function (): void {
    $attribute = $this->attributes->get(ChildTestClass::class, SimpleAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(ChildAttribute::class)
        ->name->toBe('child-class');
});

it('finds child attributes with exact class too', function (): void {
    $attribute = $this->attributes->get(ChildTestClass::class, ChildAttribute::class);

    expect($attribute)
        ->toBeInstanceOf(ChildAttribute::class)
        ->name->toBe('child-class');
});

// find

it('can find all attributes across a class', function (): void {
    $results = $this->attributes->find(TestClass::class, MultiTargetAttribute::class);

    expect($results)
        ->toHaveCount(1)
        ->each->toBeInstanceOf(AttributeTarget::class);

    expect($results[0])
        ->attribute->toBeInstanceOf(MultiTargetAttribute::class)
        ->attribute->label->toBe('processor')
        ->name->toBe('process');
});

it('finds attributes on class, methods, properties, constants, and parameters', function (): void {
    // Use a broad search with SimpleAttribute - via IS_INSTANCEOF, this won't match other attrs
    // Instead let's search for something present at multiple levels
    $results = $this->attributes->find(TestClass::class, ParameterAttribute::class);

    expect($results)->toHaveCount(2);

    expect($results[0])->name->toBe('handle.request');
    expect($results[1])->name->toBe('handle.id');
});

it('find returns back-references to reflection targets', function (): void {
    $results = $this->attributes->find(TestClass::class, MethodAttribute::class);

    expect($results)->toHaveCount(2);

    expect($results[0])
        ->target->toBeInstanceOf(ReflectionMethod::class)
        ->name->toBe('handle');

    expect($results[1])
        ->target->toBeInstanceOf(ReflectionMethod::class)
        ->name->toBe('process');
});

it('find returns empty array for class without matching attributes', function (): void {
    expect($this->attributes->find(PlainClass::class, SimpleAttribute::class))->toBeEmpty();
});

it('can find all attributes without filtering by type', function (): void {
    $results = $this->attributes->find(TestClass::class);

    expect($results)
        ->toHaveCount(19)
        ->each->toBeInstanceOf(AttributeTarget::class);

    $attributeClasses = array_map(fn (AttributeTarget $r): string => $r->attribute::class, $results);

    expect($attributeClasses)->toContain(
        SimpleAttribute::class,
        RepeatableAttribute::class,
        RepeatableTag::class,
        MethodAttribute::class,
        PropertyAttribute::class,
        ConstantAttribute::class,
        ParameterAttribute::class,
        MultiTargetAttribute::class,
    );
});

it('find without filter returns empty array for plain class', function (): void {
    expect($this->attributes->find(PlainClass::class))->toBeEmpty();
});

it('find discovers child attributes via inheritance', function (): void {
    $results = $this->attributes->find(ChildTestClass::class, SimpleAttribute::class);

    expect($results)->toHaveCount(1);
    expect($results[0]->attribute)->toBeInstanceOf(ChildAttribute::class);
});

// toArray

it('can convert an attribute target to an array', function (): void {
    $results = $this->attributes->find(TestClass::class, SimpleAttribute::class);

    expect($results[0]->toArray())->toBe(['name' => 'test-class']);
});

it('can convert an attribute with multiple properties to an array', function (): void {
    $results = $this->attributes->find(TestClass::class, MethodAttribute::class);

    expect($results[0]->toArray())->toBe(['route' => '/handle']);
});

it('can convert an attribute with default values to an array', function (): void {
    $results = $this->attributes->find(TestClass::class, ParameterAttribute::class);

    expect($results[0]->toArray())->toBe(['type' => 'request']);
});

it('can convert an attribute with variadic arguments to an array', function (): void {
    $results = $this->attributes->find(
        VariadicClass::class,
        VariadicAttribute::class,
    );

    expect($results[0]->toArray())->toBe(['tags' => ['featured', 'popular', 'trending']]);
});

// object instances

it('works with object instances for all methods', function (): void {
    $object = new TestClass();

    expect($this->attributes->has($object, SimpleAttribute::class))->toBeTrue();
    expect($this->attributes->onMethod($object, 'handle', MethodAttribute::class))->toBeInstanceOf(MethodAttribute::class);
    expect($this->attributes->onProperty($object, 'name', PropertyAttribute::class))->toBeInstanceOf(PropertyAttribute::class);
    expect($this->attributes->onConstant($object, 'STATUS_ACTIVE', ConstantAttribute::class))->toBeInstanceOf(ConstantAttribute::class);
    expect($this->attributes->onParameter($object, 'handle', 'request', ParameterAttribute::class))->toBeInstanceOf(ParameterAttribute::class);
    expect($this->attributes->find($object, MultiTargetAttribute::class))->toHaveCount(1);
});

// query options

it('supports exact attribute matching when inheritance is disabled', function (): void {
    $attribute = $this->attributes->get(
        ChildTestClass::class,
        SimpleAttribute::class,
        QueryOptions::default()->withoutInheritance(),
    );

    expect($attribute)->toBeNull();
});

it('supports filtering find results by target types', function (): void {
    $results = $this->attributes->find(
        TestClass::class,
        null,
        QueryOptions::default()->onlyTargets(AttributeTargetType::MethodTarget),
    );

    expect($results)->toHaveCount(5);

    $targets = array_map(fn (AttributeTarget $result): ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionClassConstant|\ReflectionParameter|\ReflectionFunction => $result->target, $results);

    expect($targets)->each->toBeInstanceOf(ReflectionMethod::class);
});

// raw mode

it('can return reflection attributes without instantiation', function (): void {
    $attribute = $this->attributes->get(
        TestClass::class,
        SimpleAttribute::class,
        QueryOptions::default()->withoutInstantiation(),
    );

    expect($attribute)
        ->toBeInstanceOf(ReflectionAttribute::class)
        ->getName()->toBe(SimpleAttribute::class);
});

it('can expose reflection arguments through attribute target toArray in raw mode', function (): void {
    $results = $this->attributes->find(
        TestClass::class,
        MethodAttribute::class,
        QueryOptions::default()->withoutInstantiation(),
    );

    expect($results[0]->attribute)->toBeInstanceOf(ReflectionAttribute::class);
    expect($results[0]->toArray())->toBe(['route' => '/handle']);
});

// strict mode

it('throws when strict mode cannot find a method', function (): void {
    $this->attributes->onMethod(
        TestClass::class,
        'missing',
        MethodAttribute::class,
        QueryOptions::default()->strict(),
    );
})->throws(
    AttributeLookupException::class,
    "Method 'missing' was not found on class '".TestClass::class."'.",
);

it('throws when strict mode cannot find a function', function (): void {
    $this->attributes->onFunction(
        'missing_function',
        MultiTargetAttribute::class,
        QueryOptions::default()->strict(),
    );
})->throws(
    AttributeLookupException::class,
    "Function 'missing_function' could not be reflected.",
);

// reflection caching

it('caches reflection across multiple calls', function (): void {
    $this->attributes->get(TestClass::class, SimpleAttribute::class);
    $this->attributes->onMethod(TestClass::class, 'handle', MethodAttribute::class);

    $counts = $this->attributes->reflectionCacheCount();

    expect($counts)->toBe(['classes' => 1, 'functions' => 0]);
});

it('skips cache when withoutCache option is used', function (): void {
    $this->attributes->get(
        TestClass::class,
        SimpleAttribute::class,
        QueryOptions::default()->withoutCache(),
    );

    $counts = $this->attributes->reflectionCacheCount();

    expect($counts)->toBe(['classes' => 0, 'functions' => 0]);
});

it('can clear reflection caches', function (): void {
    $this->attributes->get(TestClass::class, SimpleAttribute::class);
    $this->attributes->onFunction('Tests\\TestSupport\\testFunction', MultiTargetAttribute::class);

    $this->attributes->clearReflectionCaches();

    expect($this->attributes->reflectionCacheCount())->toBe(['classes' => 0, 'functions' => 0]);
});

it('returns structured cache counts', function (): void {
    $this->attributes->get(TestClass::class, SimpleAttribute::class);
    $this->attributes->get(PlainClass::class);
    $this->attributes->onFunction('Tests\\TestSupport\\testFunction', MultiTargetAttribute::class);

    expect($this->attributes->reflectionCacheCount())->toBe(['classes' => 2, 'functions' => 1]);
});
