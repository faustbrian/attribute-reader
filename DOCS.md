# attribute-reader documentation

## Introduction

PHP 8 introduced attributes, a way to add structured metadata to classes,
methods, properties, constants, parameters, and functions. The reflection API
to read them is powerful but verbose.

Imagine you have a controller with a `Route` attribute:

```php
#[Route('/my-controller')]
class MyController
{
    // ...
}
```

Getting that attribute instance using native PHP requires this:

```php
$reflection = new ReflectionClass(MyController::class);
$attributes = $reflection->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

if (count($attributes) > 0) {
    $route = $attributes[0]->newInstance();
}
```

With this package, it becomes a single call:

```php
use Cline\AttributeReader\Attributes;

$route = Attributes::get(MyController::class, Route::class);
```

By default, the package handles instantiation, `IS_INSTANCEOF` matching, and
returns `null` or `[]` for missing targets instead of throwing.

It works on all attribute targets: classes, methods, properties, constants,
parameters, and functions. It supports repeatable attributes and can discover
all usages of an attribute across an entire class.

The API also supports query options for strict mode, raw reflection attributes,
exact class matching, target filtering, and reflection caching.

## Reading Class Attributes

All examples on this section use the following attribute and class:

```php
#[Attribute]
class Description
{
    public function __construct(
        public string $text,
    ) {}
}

#[Description('A user account')]
class User {}
```

### Getting an attribute

Use `get()` to retrieve a single attribute instance from a class. Returns
`null` if the attribute is not present.

```php
use Cline\AttributeReader\Attributes;

$description = Attributes::get(User::class, Description::class);

$description->text; // 'A user account'
```

### Checking for an attribute

Use `has()` to check if a class has a specific attribute:

```php
Attributes::has(User::class, Description::class); // true
Attributes::has(User::class, SomeOtherAttribute::class); // false
```

### Repeated attributes

If an attribute is marked as `IS_REPEATABLE`, use `getAll()` to retrieve all
instances. Returns an empty array when no matching attributes exist.

```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Tag
{
    public function __construct(public string $name) {}
}

#[Tag('featured')]
#[Tag('popular')]
class Post {}

$tags = Attributes::getAll(Post::class, Tag::class);

$tags[0]->name; // 'featured'
$tags[1]->name; // 'popular'
```

### Without an attribute filter

All class-level methods also accept an optional attribute parameter. When
omitted, they work with any attribute:

```php
Attributes::get(User::class);    // first attribute, regardless of type
Attributes::has(User::class);    // true if the class has any attribute
Attributes::getAll(User::class); // all attributes on the class
```

### Using object instances

All methods accept either a class string or an object instance:

```php
$user = new User();

Attributes::get($user, Description::class);
Attributes::has($user, Description::class);
```

## Reading From Specific Targets

Beyond class-level attributes, you can read attributes from methods,
properties, constants, parameters, and standalone functions.

All examples on this section use the following setup:

```php
use Cline\AttributeReader\Attributes;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(public string $path) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(public string $name) {}
}

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Label
{
    public function __construct(public string $text) {}
}

#[Attribute(Attribute::TARGET_PARAMETER)]
class FromQuery
{
    public function __construct(public string $key = '') {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(public string $name) {}
}

class UserController
{
    #[Label('Active')]
    public const STATUS_ACTIVE = 'active';

    #[Column('email_address')]
    public string $email;

    #[Route('/users')]
    #[Middleware('auth')]
    #[Middleware('verified')]
    public function index(#[FromQuery('q')] string $query) {}
}
```

### Methods

```php
$route = Attributes::onMethod(UserController::class, 'index', Route::class);

$route->path; // '/users'
```

### Properties

```php
$column = Attributes::onProperty(UserController::class, 'email', Column::class);

$column->name; // 'email_address'
```

### Constants

```php
$label = Attributes::onConstant(UserController::class, 'STATUS_ACTIVE', Label::class);

$label->text; // 'Active'
```

### Parameters

Specify both the method name and parameter name:

```php
$fromQuery = Attributes::onParameter(
    UserController::class,
    'index',
    'query',
    FromQuery::class,
);

$fromQuery->key; // 'q'
```

### Functions

For standalone functions, use `onFunction()` with the fully qualified function
name.

```php
#[Attribute]
class Deprecated
{
    public function __construct(public string $reason = '') {}
}

#[Deprecated('Use newHelper() instead')]
function oldHelper() {}

$deprecated = Attributes::onFunction('oldHelper', Deprecated::class);

$deprecated->reason; // 'Use newHelper() instead'

// For namespaced functions:
// Attributes::onFunction('App\\Helpers\\oldHelper', Deprecated::class);
```

If the function does not exist, `onFunction()` returns `null` by default.

### Repeated attributes

When an attribute is repeatable, use the `getAllOn*` methods to retrieve all
instances:

```php
$middlewares = Attributes::getAllOnMethod(
    UserController::class,
    'index',
    Middleware::class,
);

$middlewares[0]->name; // 'auth'
$middlewares[1]->name; // 'verified'
```

The same pattern is available for all target types:

```php
Attributes::getAllOnMethod($class, $method, $attribute);                // array
Attributes::getAllOnProperty($class, $property, $attribute);            // array
Attributes::getAllOnConstant($class, $constant, $attribute);            // array
Attributes::getAllOnParameter($class, $method, $parameter, $attribute); // array
Attributes::getAllOnFunction($function, $attribute);                    // array
```

### Getting all attributes without filtering

All `on*` and `getAllOn*` methods accept an optional attribute parameter. When
omitted, they return all attributes on that target regardless of type.

```php
Attributes::onMethod($class, $method);
Attributes::onProperty($class, $property);
Attributes::onConstant($class, $constant);
Attributes::onParameter($class, $method, $parameter);
Attributes::onFunction($function);

Attributes::getAllOnMethod($class, $method);
Attributes::getAllOnProperty($class, $property);
Attributes::getAllOnConstant($class, $constant);
Attributes::getAllOnParameter($class, $method, $parameter);
Attributes::getAllOnFunction($function);
```

### Strict mode for missing targets

If you want missing methods/properties/constants/parameters/functions to throw
instead of returning `null` or `[]`, pass strict query options.

```php
use Cline\AttributeReader\Exceptions\AttributeLookupException;
use Cline\AttributeReader\QueryOptions;

try {
    Attributes::onMethod(
        UserController::class,
        'missingMethod',
        Route::class,
        QueryOptions::default()->strict(),
    );
} catch (AttributeLookupException $exception) {
    // Handle strict lookup failures.
}
```

## Discovering Attributes

The `find()` method searches an entire class for usages of an attribute: on
the class itself, all methods, properties, constants, and method parameters.

### Basic usage

```php
use Cline\AttributeReader\Attributes;

#[Attribute(Attribute::TARGET_ALL)]
class Validate
{
    public function __construct(public string $rule = 'required') {}
}

#[Validate('exists:forms')]
class ContactForm
{
    #[Validate('string|max:255')]
    public string $name;

    #[Validate('email')]
    public string $email;

    public function submit(#[Validate('array')] array $data) {}
}

$results = Attributes::find(ContactForm::class, Validate::class);

count($results); // 4
```

### The AttributeTarget object

Each result is an `AttributeTarget` instance with three properties:

```php
use Cline\AttributeReader\AttributeTarget;

foreach ($results as $result) {
    $result->attribute; // Instantiated attribute or ReflectionAttribute in raw mode
    $result->target;    // Reflection object
    $result->name;      // e.g. 'name', 'submit', 'submit.data'
}
```

For parameters, the name is formatted as `method.parameter`.

### Getting attribute values as an array

Use `toArray()` to get all attribute properties as a keyed array. This is
useful when you do not know the attribute's structure upfront.

```php
foreach ($results as $result) {
    $result->toArray();
}
```

In raw mode, `toArray()` returns `ReflectionAttribute::getArguments()`.

### Using the reflection target

The `target` property gives direct access to the underlying reflection object.

```php
foreach (Attributes::find(ContactForm::class, Validate::class) as $result) {
    if ($result->target instanceof ReflectionProperty) {
        echo $result->target->getType();
    }
}
```

### Finding all attributes

You can call `find()` without an attribute filter to get every attribute on a
class.

```php
$results = Attributes::find(ContactForm::class);
```

### Filtering by target type

Use `QueryOptions::onlyTargets()` to limit discovery to specific targets.

```php
use Cline\AttributeReader\AttributeTargetType;
use Cline\AttributeReader\QueryOptions;

$methodOnly = Attributes::find(
    ContactForm::class,
    null,
    QueryOptions::default()->onlyTargets(AttributeTargetType::MethodTarget),
);
```

## Attribute Inheritance

By default, methods in this package use
`ReflectionAttribute::IS_INSTANCEOF`. Child attributes match when you query for
a parent class.

### Example

```php
#[Attribute(Attribute::TARGET_CLASS)]
class CacheStrategy
{
    public function __construct(public int $ttl = 3600) {}
}

#[Attribute(Attribute::TARGET_CLASS)]
class AggressiveCache extends CacheStrategy
{
    public function __construct()
    {
        parent::__construct(ttl: 86400);
    }
}

#[AggressiveCache]
class ProductCatalog {}
```

Querying for the parent `CacheStrategy` finds `AggressiveCache`:

```php
use Cline\AttributeReader\Attributes;

$cache = Attributes::get(ProductCatalog::class, CacheStrategy::class);

$cache instanceof AggressiveCache; // true
$cache->ttl; // 86400
```

Querying for the exact child class also works:

```php
$cache = Attributes::get(ProductCatalog::class, AggressiveCache::class);
```

This behavior applies to all reading and discovery methods.

### Exact matching (disable inheritance)

Use query options to disable inheritance matching and require exact attribute
class matches.

```php
use Cline\AttributeReader\QueryOptions;

$cache = Attributes::get(
    ProductCatalog::class,
    CacheStrategy::class,
    QueryOptions::default()->withoutInheritance(),
);

$cache; // null (because only AggressiveCache is present)
```
