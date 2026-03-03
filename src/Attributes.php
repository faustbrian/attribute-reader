<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader;

use Cline\AttributeReader\Exceptions\AttributeConstantNotFoundException;
use Cline\AttributeReader\Exceptions\AttributeFunctionNotFoundException;
use Cline\AttributeReader\Exceptions\AttributeLookupException;
use Cline\AttributeReader\Exceptions\AttributeMethodNotFoundException;
use Cline\AttributeReader\Exceptions\AttributeParameterNotFoundException;
use Cline\AttributeReader\Exceptions\AttributePropertyNotFoundException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

use function array_map;
use function count;
use function is_object;

/**
 * Instance-based API for reading PHP 8 attributes from classes, class members, and functions.
 *
 * Provides targeted lookups (first match or all matches) for every PHP declaration site —
 * class, method, property, constant, parameter, and function — as well as a broad {@see find()}
 * scan that traverses all configured target types and returns {@see AttributeTarget} pairs.
 *
 * Behavior is controlled through {@see QueryOptions}: inheritance traversal, strict error
 * semantics, attribute instantiation, reflection caching, and target-type filtering can all
 * be adjusted without modifying the reader instance.
 *
 * ```php
 * $reader = Attributes::make();
 *
 * // Retrieve the first Route attribute from any method on a controller.
 * $route = $reader->onMethod(UserController::class, 'index', Route::class);
 *
 * // Collect every attribute across the entire class hierarchy.
 * $all = $reader->find(UserController::class);
 *
 * // Strict lookup — throws AttributeMethodNotFoundException if 'missing' does not exist.
 * $reader->onMethod(
 *     UserController::class,
 *     'missing',
 *     Route::class,
 *     QueryOptions::default()->strict(),
 * );
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Attributes
{
    /**
     * Cache of `ReflectionClass` instances keyed by class name, shared across all
     * queries on this `Attributes` instance when caching is enabled.
     *
     * @var array<string, ReflectionClass<object>>
     */
    private array $classReflectionCache = [];

    /**
     * Cache of `ReflectionFunction` instances keyed by function name, shared across
     * all queries on this `Attributes` instance when caching is enabled.
     *
     * @var array<string, ReflectionFunction>
     */
    private array $functionReflectionCache = [];

    /**
     * Create a new `Attributes` reader instance.
     *
     * Using a shared instance allows the reflection cache to be reused across multiple
     * queries on the same class or function within a single request or test run.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Get the first attribute declared directly on a class.
     *
     * When `$attribute` is `null` the first attribute of any type is returned.
     * Returns `null` if no matching attribute is found.
     *
     * @param  class-string|object $class     The class name or an object instance to inspect.
     * @param  null|class-string   $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions   $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws ReflectionException When the class cannot be reflected.
     * @return null|object         The instantiated attribute, or a `ReflectionAttribute` if instantiation is disabled.
     */
    public function get(string|object $class, ?string $attribute = null, ?QueryOptions $options = null): ?object
    {
        $queryOptions = $this->options($options);

        return $this->firstFrom(
            $this->castAttributes($this->reflect($class, $queryOptions)->getAttributes(...$this->attributeArgs($attribute, $queryOptions))),
            $queryOptions,
        );
    }

    /**
     * Determine whether a class has at least one matching attribute declared on it.
     *
     * @param  class-string|object $class     The class name or an object instance to inspect.
     * @param  null|class-string   $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions   $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws ReflectionException When the class cannot be reflected.
     * @return bool                `true` if at least one matching attribute exists on the class.
     */
    public function has(string|object $class, ?string $attribute = null, ?QueryOptions $options = null): bool
    {
        $queryOptions = $this->options($options);

        return $this->reflect($class, $queryOptions)->getAttributes(...$this->attributeArgs($attribute, $queryOptions)) !== [];
    }

    /**
     * Get all attributes declared directly on a class.
     *
     * @param  class-string|object $class     The class name or an object instance to inspect.
     * @param  null|class-string   $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions   $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws ReflectionException When the class cannot be reflected.
     * @return list<object>        All matching instantiated attributes (or `ReflectionAttribute` objects if instantiation is disabled).
     */
    public function getAll(string|object $class, ?string $attribute = null, ?QueryOptions $options = null): array
    {
        $queryOptions = $this->options($options);

        return $this->resolveAll(
            $this->castAttributes($this->reflect($class, $queryOptions)->getAttributes(...$this->attributeArgs($attribute, $queryOptions))),
            $queryOptions,
        );
    }

    /**
     * Get all attributes declared on a specific class method.
     *
     * @param  class-string|object              $class     The class name or an object instance to inspect.
     * @param  string                           $method    The method name to look up.
     * @param  null|class-string                $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeMethodNotFoundException When strict mode is enabled and the method does not exist.
     * @throws ReflectionException              When the class cannot be reflected.
     * @return list<object>                     All matching instantiated attributes (or `ReflectionAttribute` objects if instantiation is disabled).
     */
    public function getAllOnMethod(
        string|object $class,
        string $method,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        return $this->fromMethod($class, $method, $attribute, $this->options($options), true);
    }

    /**
     * Get all attributes declared on a specific class property.
     *
     * @param  class-string|object                $class     The class name or an object instance to inspect.
     * @param  string                             $property  The property name to look up.
     * @param  null|class-string                  $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                  $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributePropertyNotFoundException When strict mode is enabled and the property does not exist.
     * @throws ReflectionException                When the class cannot be reflected.
     * @return list<object>                       All matching instantiated attributes (or `ReflectionAttribute` objects if instantiation is disabled).
     */
    public function getAllOnProperty(
        string|object $class,
        string $property,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        return $this->fromProperty($class, $property, $attribute, $this->options($options), true);
    }

    /**
     * Get all attributes declared on a specific class constant.
     *
     * @param  class-string|object                $class     The class name or an object instance to inspect.
     * @param  string                             $constant  The constant name to look up.
     * @param  null|class-string                  $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                  $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeConstantNotFoundException When strict mode is enabled and the constant does not exist.
     * @throws ReflectionException                When the class cannot be reflected.
     * @return list<object>                       All matching instantiated attributes (or `ReflectionAttribute` objects if instantiation is disabled).
     */
    public function getAllOnConstant(
        string|object $class,
        string $constant,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        return $this->fromConstant($class, $constant, $attribute, $this->options($options), true);
    }

    /**
     * Get all attributes declared on a specific method parameter.
     *
     * @param  class-string|object                 $class     The class name or an object instance to inspect.
     * @param  string                              $method    The method name that owns the parameter.
     * @param  string                              $parameter The parameter name to look up.
     * @param  null|class-string                   $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                   $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeMethodNotFoundException    When strict mode is enabled and the method does not exist.
     * @throws AttributeParameterNotFoundException When strict mode is enabled and the parameter does not exist on the method.
     * @throws ReflectionException                 When the class cannot be reflected.
     * @return list<object>                        All matching instantiated attributes (or `ReflectionAttribute` objects if instantiation is disabled).
     */
    public function getAllOnParameter(
        string|object $class,
        string $method,
        string $parameter,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        return $this->fromParameter($class, $method, $parameter, $attribute, $this->options($options), true);
    }

    /**
     * Get all attributes declared on a named function.
     *
     * @param  string                             $function  Fully-qualified function name to reflect.
     * @param  null|class-string                  $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                  $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeFunctionNotFoundException When strict mode is enabled and the function cannot be reflected.
     * @return list<object>                       All matching instantiated attributes (or `ReflectionAttribute` objects if instantiation is disabled).
     */
    public function getAllOnFunction(
        string $function,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        return $this->fromFunction($function, $attribute, $this->options($options), true);
    }

    /**
     * Get the first attribute declared on a specific class method.
     *
     * @param  class-string|object              $class     The class name or an object instance to inspect.
     * @param  string                           $method    The method name to look up.
     * @param  null|class-string                $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeMethodNotFoundException When strict mode is enabled and the method does not exist.
     * @throws ReflectionException              When the class cannot be reflected.
     * @return null|object                      The first matching instantiated attribute, or `null` if none found.
     */
    public function onMethod(
        string|object $class,
        string $method,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        return $this->fromMethod($class, $method, $attribute, $this->options($options), false);
    }

    /**
     * Get the first attribute declared on a specific class property.
     *
     * @param  class-string|object                $class     The class name or an object instance to inspect.
     * @param  string                             $property  The property name to look up.
     * @param  null|class-string                  $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                  $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributePropertyNotFoundException When strict mode is enabled and the property does not exist.
     * @throws ReflectionException                When the class cannot be reflected.
     * @return null|object                        The first matching instantiated attribute, or `null` if none found.
     */
    public function onProperty(
        string|object $class,
        string $property,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        return $this->fromProperty($class, $property, $attribute, $this->options($options), false);
    }

    /**
     * Get the first attribute declared on a specific class constant.
     *
     * @param  class-string|object                $class     The class name or an object instance to inspect.
     * @param  string                             $constant  The constant name to look up.
     * @param  null|class-string                  $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                  $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeConstantNotFoundException When strict mode is enabled and the constant does not exist.
     * @throws ReflectionException                When the class cannot be reflected.
     * @return null|object                        The first matching instantiated attribute, or `null` if none found.
     */
    public function onConstant(
        string|object $class,
        string $constant,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        return $this->fromConstant($class, $constant, $attribute, $this->options($options), false);
    }

    /**
     * Get the first attribute declared on a specific method parameter.
     *
     * @param  class-string|object                 $class     The class name or an object instance to inspect.
     * @param  string                              $method    The method name that owns the parameter.
     * @param  string                              $parameter The parameter name to look up.
     * @param  null|class-string                   $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                   $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeMethodNotFoundException    When strict mode is enabled and the method does not exist.
     * @throws AttributeParameterNotFoundException When strict mode is enabled and the parameter does not exist on the method.
     * @throws ReflectionException                 When the class cannot be reflected.
     * @return null|object                         The first matching instantiated attribute, or `null` if none found.
     */
    public function onParameter(
        string|object $class,
        string $method,
        string $parameter,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        return $this->fromParameter($class, $method, $parameter, $attribute, $this->options($options), false);
    }

    /**
     * Get the first attribute declared on a named function.
     *
     * @param  string                             $function  Fully-qualified function name to reflect.
     * @param  null|class-string                  $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions                  $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws AttributeFunctionNotFoundException When strict mode is enabled and the function cannot be reflected.
     * @return null|object                        The first matching instantiated attribute, or `null` if none found.
     */
    public function onFunction(
        string $function,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        return $this->fromFunction($function, $attribute, $this->options($options), false);
    }

    /**
     * Scan a class for attributes across all configured target types and return matched pairs.
     *
     * Traverses every target type enabled in `$options` (class, methods, parameters, properties,
     * and constants by default) and returns one {@see AttributeTarget} per match. Each pair carries
     * the resolved attribute and the reflection object for the declaration site.
     *
     * ```php
     * $targets = Attributes::make()->find(MyController::class, Route::class);
     *
     * foreach ($targets as $target) {
     *     // $target->name is "index", "show", etc. for methods
     *     // $target->attribute is the instantiated Route attribute
     *     echo $target->name . ': ' . $target->attribute->path;
     * }
     * ```
     *
     * @param  class-string|object   $class     The class name or an object instance to inspect.
     * @param  null|class-string     $attribute Fully-qualified attribute class name to filter by, or `null` for any.
     * @param  null|QueryOptions     $options   Query options; falls back to {@see QueryOptions::default()} when `null`.
     * @throws ReflectionException   When the class cannot be reflected.
     * @return list<AttributeTarget> All matched attribute–target pairs across the scanned declaration sites.
     */
    public function find(
        string|object $class,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = $this->options($options);
        $reflection = $this->reflect($class, $queryOptions);
        $results = [];
        $args = $this->attributeArgs($attribute, $queryOptions);

        if ($queryOptions->includes(AttributeTargetType::ClassTarget)) {
            foreach ($this->castAttributes($reflection->getAttributes(...$args)) as $attr) {
                $results[] = new AttributeTarget($this->resolve($attr, $queryOptions), $reflection, $reflection->getName());
            }
        }

        if ($queryOptions->includes(AttributeTargetType::MethodTarget) || $queryOptions->includes(AttributeTargetType::ParameterTarget)) {
            foreach ($reflection->getMethods() as $method) {
                if ($queryOptions->includes(AttributeTargetType::MethodTarget)) {
                    foreach ($this->castAttributes($method->getAttributes(...$args)) as $attr) {
                        $results[] = new AttributeTarget($this->resolve($attr, $queryOptions), $method, $method->getName());
                    }
                }

                if (!$queryOptions->includes(AttributeTargetType::ParameterTarget)) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    foreach ($this->castAttributes($parameter->getAttributes(...$args)) as $attr) {
                        $results[] = new AttributeTarget(
                            $this->resolve($attr, $queryOptions),
                            $parameter,
                            $method->getName().'.'.$parameter->getName(),
                        );
                    }
                }
            }
        }

        if ($queryOptions->includes(AttributeTargetType::PropertyTarget)) {
            foreach ($reflection->getProperties() as $property) {
                foreach ($this->castAttributes($property->getAttributes(...$args)) as $attr) {
                    $results[] = new AttributeTarget($this->resolve($attr, $queryOptions), $property, $property->getName());
                }
            }
        }

        if ($queryOptions->includes(AttributeTargetType::ConstantTarget)) {
            foreach ($reflection->getReflectionConstants() as $constant) {
                foreach ($this->castAttributes($constant->getAttributes(...$args)) as $attr) {
                    $results[] = new AttributeTarget($this->resolve($attr, $queryOptions), $constant, $constant->getName());
                }
            }
        }

        return $results;
    }

    /**
     * Flush all in-memory reflection caches held by this instance.
     *
     * Call this when classes or functions have been redefined at runtime (e.g., during
     * tests that use anonymous classes or dynamic class generation) and stale reflection
     * objects would otherwise be returned for subsequent queries.
     */
    public function clearReflectionCaches(): void
    {
        $this->classReflectionCache = [];
        $this->functionReflectionCache = [];
    }

    /**
     * Return the number of entries currently held in each reflection cache.
     *
     * Useful for debugging and asserting cache behaviour in tests.
     *
     * @return array{classes: int, functions: int}
     */
    public function reflectionCacheCount(): array
    {
        return [
            'classes' => count($this->classReflectionCache),
            'functions' => count($this->functionReflectionCache),
        ];
    }

    /**
     * Shared implementation for method-level attribute lookups.
     *
     * @param  class-string|object                     $class
     * @param  null|class-string                       $attribute
     * @param  bool                                    $all       When `true` returns all matches; when `false` returns only the first.
     * @return ($all is true ? list<object> : ?object)
     */
    private function fromMethod(string|object $class, string $method, ?string $attribute, QueryOptions $queryOptions, bool $all): object|array|null
    {
        $reflection = $this->reflect($class, $queryOptions);

        if (!$reflection->hasMethod($method)) {
            $this->throwIfStrict($queryOptions, fn (): AttributeMethodNotFoundException => AttributeMethodNotFoundException::forClass($reflection->getName(), $method));

            return $all ? [] : null;
        }

        $attrs = $this->castAttributes($reflection->getMethod($method)->getAttributes(...$this->attributeArgs($attribute, $queryOptions)));

        return $all ? $this->resolveAll($attrs, $queryOptions) : $this->firstFrom($attrs, $queryOptions);
    }

    /**
     * Shared implementation for property-level attribute lookups.
     *
     * @param  class-string|object                     $class
     * @param  null|class-string                       $attribute
     * @param  bool                                    $all       When `true` returns all matches; when `false` returns only the first.
     * @return ($all is true ? list<object> : ?object)
     */
    private function fromProperty(string|object $class, string $property, ?string $attribute, QueryOptions $queryOptions, bool $all): object|array|null
    {
        $reflection = $this->reflect($class, $queryOptions);

        if (!$reflection->hasProperty($property)) {
            $this->throwIfStrict($queryOptions, fn (): AttributePropertyNotFoundException => AttributePropertyNotFoundException::forClass($reflection->getName(), $property));

            return $all ? [] : null;
        }

        $attrs = $this->castAttributes($reflection->getProperty($property)->getAttributes(...$this->attributeArgs($attribute, $queryOptions)));

        return $all ? $this->resolveAll($attrs, $queryOptions) : $this->firstFrom($attrs, $queryOptions);
    }

    /**
     * Shared implementation for constant-level attribute lookups.
     *
     * @param  class-string|object                     $class
     * @param  null|class-string                       $attribute
     * @param  bool                                    $all       When `true` returns all matches; when `false` returns only the first.
     * @return ($all is true ? list<object> : ?object)
     */
    private function fromConstant(string|object $class, string $constant, ?string $attribute, QueryOptions $queryOptions, bool $all): object|array|null
    {
        $reflection = $this->reflect($class, $queryOptions);
        $constantReflection = $reflection->getReflectionConstant($constant);

        if ($constantReflection === false) {
            $this->throwIfStrict($queryOptions, fn (): AttributeConstantNotFoundException => AttributeConstantNotFoundException::forClass($reflection->getName(), $constant));

            return $all ? [] : null;
        }

        $attrs = $this->castAttributes($constantReflection->getAttributes(...$this->attributeArgs($attribute, $queryOptions)));

        return $all ? $this->resolveAll($attrs, $queryOptions) : $this->firstFrom($attrs, $queryOptions);
    }

    /**
     * Shared implementation for parameter-level attribute lookups.
     *
     * @param  class-string|object                     $class
     * @param  null|class-string                       $attribute
     * @param  bool                                    $all       When `true` returns all matches; when `false` returns only the first.
     * @return ($all is true ? list<object> : ?object)
     */
    private function fromParameter(string|object $class, string $method, string $parameter, ?string $attribute, QueryOptions $queryOptions, bool $all): object|array|null
    {
        $reflection = $this->reflect($class, $queryOptions);

        if (!$reflection->hasMethod($method)) {
            $this->throwIfStrict($queryOptions, fn (): AttributeMethodNotFoundException => AttributeMethodNotFoundException::forClass($reflection->getName(), $method));

            return $all ? [] : null;
        }

        $methodReflection = $reflection->getMethod($method);
        $parameterReflection = $this->findParameter($methodReflection, $parameter);

        if (!$parameterReflection instanceof ReflectionParameter) {
            $this->throwIfStrict($queryOptions, fn (): AttributeParameterNotFoundException => AttributeParameterNotFoundException::forMethod($reflection->getName(), $method, $parameter));

            return $all ? [] : null;
        }

        $attrs = $this->castAttributes($parameterReflection->getAttributes(...$this->attributeArgs($attribute, $queryOptions)));

        return $all ? $this->resolveAll($attrs, $queryOptions) : $this->firstFrom($attrs, $queryOptions);
    }

    /**
     * Shared implementation for function-level attribute lookups.
     *
     * Catches `ReflectionException` from `ReflectionFunction` construction and converts it to
     * an {@see AttributeFunctionNotFoundException} in strict mode, or silently returns empty
     * results in non-strict mode.
     *
     * @param  null|class-string                       $attribute
     * @param  bool                                    $all       When `true` returns all matches; when `false` returns only the first.
     * @return ($all is true ? list<object> : ?object)
     */
    private function fromFunction(string $function, ?string $attribute, QueryOptions $queryOptions, bool $all): object|array|null
    {
        try {
            $attrs = $this->castAttributes($this->reflectFunction($function, $queryOptions)->getAttributes(...$this->attributeArgs($attribute, $queryOptions)));

            return $all ? $this->resolveAll($attrs, $queryOptions) : $this->firstFrom($attrs, $queryOptions);
        } catch (ReflectionException $reflectionException) {
            $this->throwIfStrict($queryOptions, fn (): AttributeFunctionNotFoundException => AttributeFunctionNotFoundException::forFunction($function, $reflectionException));

            return $all ? [] : null;
        }
    }

    /**
     * Resolve or create a `ReflectionClass` for the given subject, using the cache when enabled.
     *
     * Objects are keyed by their class name so that the same `ReflectionClass` is returned
     * whether a class name string or an instance of the class is passed.
     *
     * @param  class-string|object     $class
     * @return ReflectionClass<object>
     */
    private function reflect(string|object $class, QueryOptions $options): ReflectionClass
    {
        $key = is_object($class) ? $class::class : $class;

        if ($options->useCache && isset($this->classReflectionCache[$key])) {
            return $this->classReflectionCache[$key];
        }

        $reflection = new ReflectionClass($class);

        if ($options->useCache) {
            $this->classReflectionCache[$key] = $reflection;
        }

        return $reflection;
    }

    /**
     * Resolve or create a `ReflectionFunction` for the given function name, using the cache when enabled.
     *
     * @param  string              $function Fully-qualified function name.
     * @throws ReflectionException When the function does not exist.
     */
    private function reflectFunction(string $function, QueryOptions $options): ReflectionFunction
    {
        if ($options->useCache && isset($this->functionReflectionCache[$function])) {
            return $this->functionReflectionCache[$function];
        }

        $reflection = new ReflectionFunction($function);

        if ($options->useCache) {
            $this->functionReflectionCache[$function] = $reflection;
        }

        return $reflection;
    }

    /**
     * Narrows the return type of getAttributes() to list<ReflectionAttribute<object>>
     * so the rest of the type system stays clean.
     *
     * @return list<ReflectionAttribute<object>>
     * @phpstan-ignore missingType.iterableValue (input is untyped by design; this is a type-narrowing helper)
     */
    private function castAttributes(array $attributes): array
    {
        /** @var list<ReflectionAttribute<object>> $attributes */
        return $attributes;
    }

    /**
     * Resolve and return the first element of an attribute list, or `null` when the list is empty.
     *
     * @param list<ReflectionAttribute<object>> $attributes
     */
    private function firstFrom(array $attributes, QueryOptions $options): ?object
    {
        if ($attributes === []) {
            return null;
        }

        return $this->resolve($attributes[0], $options);
    }

    /**
     * Resolve every element of an attribute list and return as a plain list.
     *
     * @param  list<ReflectionAttribute<object>> $attributes
     * @return list<object>
     */
    private function resolveAll(array $attributes, QueryOptions $options): array
    {
        return array_map(fn (ReflectionAttribute $attr): object => $this->resolve($attr, $options), $attributes);
    }

    /**
     * Instantiate a `ReflectionAttribute` or return it raw, depending on options.
     *
     * @param  ReflectionAttribute<object> $attribute
     * @return object                      The instantiated attribute, or the `ReflectionAttribute` itself when instantiation is disabled.
     */
    private function resolve(ReflectionAttribute $attribute, QueryOptions $options): object
    {
        if ($options->instantiate === false) {
            return $attribute;
        }

        /** @var object */
        return $attribute->newInstance();
    }

    /**
     * Find a parameter by name on a reflected method.
     *
     * PHP's reflection API provides no direct name-based parameter lookup, so this method
     * iterates the parameter list manually.
     *
     * @param  ReflectionMethod         $method The method whose parameters are searched.
     * @param  string                   $name   The parameter name to find (without the leading `$`).
     * @return null|ReflectionParameter The matched parameter, or `null` if not found.
     */
    private function findParameter(ReflectionMethod $method, string $name): ?ReflectionParameter
    {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * Resolve the caller-supplied options or fall back to the package defaults.
     */
    private function options(?QueryOptions $options): QueryOptions
    {
        return $options ?? QueryOptions::default();
    }

    /**
     * @param  callable(): AttributeLookupException $exception
     * @throws AttributeLookupException
     */
    private function throwIfStrict(QueryOptions $options, callable $exception): void
    {
        if ($options->strict) {
            throw $exception();
        }
    }

    /**
     * Build the argument list for `Reflection*::getAttributes()`.
     *
     * When no attribute filter is requested, an empty array is returned so that
     * `getAttributes(...[])` fetches all attributes. When a filter is supplied and
     * inheritance is enabled, `ReflectionAttribute::IS_INSTANCEOF` is appended so that
     * subclasses of the requested attribute type are also matched.
     *
     * @param  null|class-string                                                          $attribute
     * @return array{0: class-string, 1?: int-mask-of<ReflectionAttribute::IS_*>}|array{}
     */
    private function attributeArgs(?string $attribute, QueryOptions $options): array
    {
        if ($attribute === null) {
            return [];
        }

        if ($options->includeInheritance === false) {
            return [$attribute];
        }

        return [$attribute, ReflectionAttribute::IS_INSTANCEOF];
    }
}
