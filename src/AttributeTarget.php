<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

use function array_key_exists;
use function array_values;
use function get_object_vars;

/**
 * Immutable value object pairing a resolved attribute with the reflection target it was found on.
 *
 * Returned by {@see Attributes::find()} for each attribute match across a class hierarchy.
 * The `$attribute` property holds either an instantiated attribute object or a raw
 * `ReflectionAttribute` when instantiation is disabled via {@see QueryOptions::withoutInstantiation()}.
 *
 * ```php
 * $targets = Attributes::make()->find(MyClass::class, Route::class);
 *
 * foreach ($targets as $target) {
 *     echo $target->name;           // e.g. "index" for a method, "MyClass" for the class itself
 *     echo get_class($target->attribute); // e.g. "App\Attributes\Route"
 * }
 * ```
 *
 * @psalm-immutable
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class AttributeTarget
{
    /**
     * Create a new attribute target pair.
     *
     * @param object                                                                                                                     $attribute Instantiated attribute object, or a raw {@see ReflectionAttribute} instance
     *                                                                                                                                              when instantiation is disabled via {@see QueryOptions::withoutInstantiation()}.
     * @param ReflectionClass<object>|ReflectionClassConstant|ReflectionFunction|ReflectionMethod|ReflectionParameter|ReflectionProperty $target    Reflection object for the declaration site where the attribute was found.
     *                                                                                                                                              The concrete type indicates whether the attribute was on the class, a method,
     *                                                                                                                                              a property, a constant, a parameter, or a function.
     * @param string                                                                                                                     $name      Human-readable identifier for the target. For methods this is the method name,
     *                                                                                                                                              for parameters it is `"methodName.paramName"`, and for class-level targets it
     *                                                                                                                                              is the fully-qualified class name.
     */
    public function __construct(
        public object $attribute,
        public ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter|ReflectionFunction $target,
        public string $name,
    ) {}

    /**
     * Export the attribute constructor arguments as a normalized array.
     *
     * When the attribute was not instantiated the raw `ReflectionAttribute` arguments are
     * returned via {@see ReflectionAttribute::getArguments()}. For instantiated attributes,
     * public properties are extracted with {@see get_object_vars()}. If any property key is
     * an integer the result is re-indexed as a list; otherwise the associative shape is preserved.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        if ($this->attribute instanceof ReflectionAttribute) {
            return $this->attribute->getArguments();
        }

        $values = get_object_vars($this->attribute);

        if (!array_key_exists(0, $values)) {
            /** @var array<string, mixed> $values */
            return $values;
        }

        return array_values($values);
    }
}
