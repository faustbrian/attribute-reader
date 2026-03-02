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

final class Attributes
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $classReflectionCache = [];

    /** @var array<string, ReflectionFunction> */
    private static array $functionReflectionCache = [];

    /**
     * @param class-string|object $class
     * @param null|class-string   $attribute
     */
    public static function get(string|object $class, ?string $attribute = null, ?QueryOptions $options = null): ?object
    {
        $queryOptions = self::options($options);

        return self::firstFrom(
            self::reflect($class, $queryOptions)->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param class-string|object $class
     * @param null|class-string   $attribute
     */
    public static function has(string|object $class, ?string $attribute = null, ?QueryOptions $options = null): bool
    {
        $queryOptions = self::options($options);

        return self::reflect($class, $queryOptions)->getAttributes(...$queryOptions->attributeArgs($attribute)) !== [];
    }

    /**
     * @param  class-string|object $class
     * @param  null|class-string   $attribute
     * @return array<object>
     */
    public static function getAll(string|object $class, ?string $attribute = null, ?QueryOptions $options = null): array
    {
        $queryOptions = self::options($options);

        return self::resolveAll(
            self::reflect($class, $queryOptions)->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param  class-string|object $class
     * @param  null|class-string   $attribute
     * @return array<object>
     */
    public static function getAllOnMethod(
        string|object $class,
        string $method,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasMethod($method)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeMethodNotFoundException => AttributeMethodNotFoundException::forClass($reflection->getName(), $method),
            );

            return [];
        }

        return self::resolveAll(
            $reflection->getMethod($method)->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param  class-string|object $class
     * @param  null|class-string   $attribute
     * @return array<object>
     */
    public static function getAllOnProperty(
        string|object $class,
        string $property,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasProperty($property)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributePropertyNotFoundException => AttributePropertyNotFoundException::forClass($reflection->getName(), $property),
            );

            return [];
        }

        return self::resolveAll(
            $reflection->getProperty($property)->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param  class-string|object $class
     * @param  null|class-string   $attribute
     * @return array<object>
     */
    public static function getAllOnConstant(
        string|object $class,
        string $constant,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasConstant($constant)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeConstantNotFoundException => AttributeConstantNotFoundException::forClass($reflection->getName(), $constant),
            );

            return [];
        }

        $constantReflection = $reflection->getReflectionConstant($constant);

        if ($constantReflection === false) {
            return [];
        }

        return self::resolveAll(
            $constantReflection->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param  class-string|object $class
     * @param  null|class-string   $attribute
     * @return array<object>
     */
    public static function getAllOnParameter(
        string|object $class,
        string $method,
        string $parameter,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasMethod($method)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeMethodNotFoundException => AttributeMethodNotFoundException::forClass($reflection->getName(), $method),
            );

            return [];
        }

        $methodReflection = $reflection->getMethod($method);
        $parameterReflection = self::findParameter($methodReflection, $parameter);

        if (!$parameterReflection instanceof ReflectionParameter) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeParameterNotFoundException => AttributeParameterNotFoundException::forMethod($reflection->getName(), $method, $parameter),
            );

            return [];
        }

        return self::resolveAll(
            $parameterReflection->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param  null|class-string $attribute
     * @return array<object>
     */
    public static function getAllOnFunction(
        string $function,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = self::options($options);

        try {
            return self::resolveAll(
                self::reflectFunction($function, $queryOptions)->getAttributes(...$queryOptions->attributeArgs($attribute)),
                $queryOptions,
            );
        } catch (ReflectionException $reflectionException) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeFunctionNotFoundException => AttributeFunctionNotFoundException::forFunction($function, $reflectionException),
            );

            return [];
        }
    }

    /**
     * @param class-string|object $class
     * @param null|class-string   $attribute
     */
    public static function onMethod(
        string|object $class,
        string $method,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasMethod($method)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeMethodNotFoundException => AttributeMethodNotFoundException::forClass($reflection->getName(), $method),
            );

            return null;
        }

        return self::firstFrom(
            $reflection->getMethod($method)->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param class-string|object $class
     * @param null|class-string   $attribute
     */
    public static function onProperty(
        string|object $class,
        string $property,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasProperty($property)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributePropertyNotFoundException => AttributePropertyNotFoundException::forClass($reflection->getName(), $property),
            );

            return null;
        }

        return self::firstFrom(
            $reflection->getProperty($property)->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param class-string|object $class
     * @param null|class-string   $attribute
     */
    public static function onConstant(
        string|object $class,
        string $constant,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasConstant($constant)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeConstantNotFoundException => AttributeConstantNotFoundException::forClass($reflection->getName(), $constant),
            );

            return null;
        }

        $constantReflection = $reflection->getReflectionConstant($constant);

        if ($constantReflection === false) {
            return null;
        }

        return self::firstFrom(
            $constantReflection->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param class-string|object $class
     * @param null|class-string   $attribute
     */
    public static function onParameter(
        string|object $class,
        string $method,
        string $parameter,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);

        if (!$reflection->hasMethod($method)) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeMethodNotFoundException => AttributeMethodNotFoundException::forClass($reflection->getName(), $method),
            );

            return null;
        }

        $methodReflection = $reflection->getMethod($method);
        $parameterReflection = self::findParameter($methodReflection, $parameter);

        if (!$parameterReflection instanceof ReflectionParameter) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeParameterNotFoundException => AttributeParameterNotFoundException::forMethod($reflection->getName(), $method, $parameter),
            );

            return null;
        }

        return self::firstFrom(
            $parameterReflection->getAttributes(...$queryOptions->attributeArgs($attribute)),
            $queryOptions,
        );
    }

    /**
     * @param null|class-string $attribute
     */
    public static function onFunction(
        string $function,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): ?object {
        $queryOptions = self::options($options);

        try {
            return self::firstFrom(
                self::reflectFunction($function, $queryOptions)->getAttributes(...$queryOptions->attributeArgs($attribute)),
                $queryOptions,
            );
        } catch (ReflectionException $reflectionException) {
            self::throwIfStrict(
                $queryOptions,
                fn (): AttributeFunctionNotFoundException => AttributeFunctionNotFoundException::forFunction($function, $reflectionException),
            );

            return null;
        }
    }

    /**
     * @param  class-string|object    $class
     * @param  null|class-string      $attribute
     * @return array<AttributeTarget>
     */
    public static function find(
        string|object $class,
        ?string $attribute = null,
        ?QueryOptions $options = null,
    ): array {
        $queryOptions = self::options($options);
        $reflection = self::reflect($class, $queryOptions);
        $results = [];
        $args = $queryOptions->attributeArgs($attribute);

        if ($queryOptions->includes(AttributeTargetType::ClassTarget)) {
            foreach ($reflection->getAttributes(...$args) as $attr) {
                $results[] = new AttributeTarget(self::resolve($attr, $queryOptions), $reflection, $reflection->getName());
            }
        }

        if ($queryOptions->includes(AttributeTargetType::MethodTarget) || $queryOptions->includes(AttributeTargetType::ParameterTarget)) {
            foreach ($reflection->getMethods() as $method) {
                if ($queryOptions->includes(AttributeTargetType::MethodTarget)) {
                    foreach ($method->getAttributes(...$args) as $attr) {
                        $results[] = new AttributeTarget(self::resolve($attr, $queryOptions), $method, $method->getName());
                    }
                }

                if (!$queryOptions->includes(AttributeTargetType::ParameterTarget)) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    foreach ($parameter->getAttributes(...$args) as $attr) {
                        $results[] = new AttributeTarget(
                            self::resolve($attr, $queryOptions),
                            $parameter,
                            $method->getName().'.'.$parameter->getName(),
                        );
                    }
                }
            }
        }

        if ($queryOptions->includes(AttributeTargetType::PropertyTarget)) {
            foreach ($reflection->getProperties() as $property) {
                foreach ($property->getAttributes(...$args) as $attr) {
                    $results[] = new AttributeTarget(self::resolve($attr, $queryOptions), $property, $property->getName());
                }
            }
        }

        if ($queryOptions->includes(AttributeTargetType::ConstantTarget)) {
            foreach ($reflection->getReflectionConstants() as $constant) {
                foreach ($constant->getAttributes(...$args) as $attr) {
                    $results[] = new AttributeTarget(self::resolve($attr, $queryOptions), $constant, $constant->getName());
                }
            }
        }

        return $results;
    }

    public static function clearReflectionCaches(): void
    {
        self::$classReflectionCache = [];
        self::$functionReflectionCache = [];
    }

    /**
     * @return array{classes: int, functions: int}
     */
    public static function reflectionCacheCount(): array
    {
        return [
            'classes' => count(self::$classReflectionCache),
            'functions' => count(self::$functionReflectionCache),
        ];
    }

    /**
     * @return ReflectionClass<object>
     */
    private static function reflect(string|object $class, QueryOptions $options): ReflectionClass
    {
        if (is_object($class)) {
            $name = $class::class;

            if ($options->useCache === false) {
                return new ReflectionClass($class);
            }

            return self::$classReflectionCache[$name] ??= new ReflectionClass($class);
        }

        /** @var class-string $className */
        $className = $class;

        if ($options->useCache === false) {
            return new ReflectionClass($className);
        }

        return self::$classReflectionCache[$className] ??= new ReflectionClass($className);
    }

    private static function reflectFunction(string $function, QueryOptions $options): ReflectionFunction
    {
        if ($options->useCache === false) {
            return new ReflectionFunction($function);
        }

        return self::$functionReflectionCache[$function] ??= new ReflectionFunction($function);
    }

    /**
     * @param array<object> $attributes
     */
    private static function firstFrom(array $attributes, QueryOptions $options): ?object
    {
        if ($attributes === []) {
            return null;
        }

        return self::resolve($attributes[0], $options);
    }

    /**
     * @param  array<object> $attributes
     * @return array<object>
     */
    private static function resolveAll(array $attributes, QueryOptions $options): array
    {
        return array_map(fn (object $attr): object => self::resolve($attr, $options), $attributes);
    }

    private static function resolve(object $attribute, QueryOptions $options): object
    {
        if (!$attribute instanceof ReflectionAttribute) {
            return $attribute;
        }

        if ($options->instantiate === false) {
            return $attribute;
        }

        return (object) $attribute->newInstance();
    }

    private static function findParameter(ReflectionMethod $method, string $name): ?ReflectionParameter
    {
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        return null;
    }

    private static function options(?QueryOptions $options): QueryOptions
    {
        return $options ?? QueryOptions::default();
    }

    /**
     * @param callable(): AttributeLookupException $exception
     */
    private static function throwIfStrict(QueryOptions $options, callable $exception): void
    {
        if ($options->strict) {
            throw $exception();
        }
    }
}
