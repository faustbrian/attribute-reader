<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader;

use function in_array;

/**
 * Immutable value object controlling how an attribute query is executed.
 *
 * All mutating methods return a new instance rather than modifying the receiver,
 * which makes it safe to share a base configuration and fork specialized variants:
 *
 * ```php
 * $base = QueryOptions::default();
 *
 * // Strict scan restricted to methods only — throws on missing targets.
 * $strict = $base->strict()->onlyTargets(AttributeTargetType::MethodTarget);
 *
 * // Lightweight scan without reflection caching or attribute instantiation.
 * $light = $base->withoutCache()->withoutInstantiation();
 * ```
 *
 * @psalm-immutable
 * @phpstan-type TargetList array<AttributeTargetType>
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class QueryOptions
{
    /**
     * Create a new options instance with explicit values for every setting.
     *
     * The constructor is private; use {@see QueryOptions::default()} as a starting
     * point and chain wither methods to customize individual options.
     *
     * @param bool       $includeInheritance When `true`, attribute lookups traverse the class
     *                                       hierarchy via `ReflectionAttribute::IS_INSTANCEOF`,
     *                                       matching parent and interface attribute types.
     *                                       Set to `false` via {@see self::withoutInheritance()} for
     *                                       exact-type matching only.
     * @param bool       $strict             When `true`, missing targets (method, property, constant,
     *                                       parameter, or function) throw the appropriate
     *                                       {@see Exceptions\AttributeLookupException}
     *                                       subclass rather than returning `null` or `[]`.
     *                                       Enable via {@see self::strict()}.
     * @param bool       $instantiate        When `true`, each matched `ReflectionAttribute` is
     *                                       instantiated via `newInstance()` before being returned.
     *                                       Set to `false` via {@see self::withoutInstantiation()} to
     *                                       receive raw `ReflectionAttribute` objects instead.
     * @param bool       $useCache           When `true`, `ReflectionClass` and `ReflectionFunction`
     *                                       instances are stored in an in-memory cache keyed by class
     *                                       or function name to avoid redundant reflection work within
     *                                       the same `Attributes` instance lifetime.
     * @param TargetList $targets            The set of {@see AttributeTargetType} values that the
     *                                       query will traverse. Restricting targets reduces unnecessary
     *                                       reflection calls on large class hierarchies.
     */
    private function __construct(
        public bool $includeInheritance,
        public bool $strict,
        public bool $instantiate,
        public bool $useCache,
        public array $targets,
    ) {}

    /**
     * Create the default query options suitable for most attribute lookups.
     *
     * Enables inheritance traversal, attribute instantiation, and reflection caching.
     * All five target types are included. Strict mode is off, so missing targets return
     * `null` or `[]` rather than throwing.
     */
    public static function default(): self
    {
        return new self(
            includeInheritance: true,
            strict: false,
            instantiate: true,
            useCache: true,
            targets: [
                AttributeTargetType::ClassTarget,
                AttributeTargetType::MethodTarget,
                AttributeTargetType::PropertyTarget,
                AttributeTargetType::ConstantTarget,
                AttributeTargetType::ParameterTarget,
            ],
        );
    }

    /**
     * Return a copy that disables inheritance traversal during attribute lookup.
     *
     * When inheritance is disabled, `ReflectionAttribute::IS_INSTANCEOF` is not passed
     * to `getAttributes()`, so only attributes whose class matches the requested name
     * exactly are returned. Useful when you need to distinguish between a base attribute
     * type and its subclasses.
     */
    public function withoutInheritance(): self
    {
        return new self(
            includeInheritance: false,
            strict: $this->strict,
            instantiate: $this->instantiate,
            useCache: $this->useCache,
            targets: $this->targets,
        );
    }

    /**
     * Return a copy that throws on missing targets instead of returning empty results.
     *
     * In strict mode, any lookup against a method, property, constant, parameter, or
     * function that does not exist on the subject class causes the relevant
     * {@see Exceptions\AttributeLookupException} subclass to be
     * thrown. This is preferable in contexts where a missing target indicates a
     * programming error rather than an expected absence.
     */
    public function strict(): self
    {
        return new self(
            includeInheritance: $this->includeInheritance,
            strict: true,
            instantiate: $this->instantiate,
            useCache: $this->useCache,
            targets: $this->targets,
        );
    }

    /**
     * Return a copy that returns raw `ReflectionAttribute` objects instead of instantiating them.
     *
     * Skipping instantiation avoids executing attribute constructors, which is useful
     * when you only need to inspect attribute names or constructor arguments without
     * paying the cost of object creation. The returned `ReflectionAttribute` instances
     * can still be instantiated on demand via `newInstance()`.
     */
    public function withoutInstantiation(): self
    {
        return new self(
            includeInheritance: $this->includeInheritance,
            strict: $this->strict,
            instantiate: false,
            useCache: $this->useCache,
            targets: $this->targets,
        );
    }

    /**
     * Return a copy that disables the in-memory reflection cache.
     *
     * By default, `ReflectionClass` and `ReflectionFunction` instances are cached inside
     * the `Attributes` instance to avoid redundant reflection work. Disabling the cache
     * is useful in test scenarios where classes may be redefined between calls, or when
     * memory footprint is a concern for very large sets of unique classes.
     */
    public function withoutCache(): self
    {
        return new self(
            includeInheritance: $this->includeInheritance,
            strict: $this->strict,
            instantiate: $this->instantiate,
            useCache: false,
            targets: $this->targets,
        );
    }

    /**
     * Return a copy that limits traversal to the specified target types.
     *
     * Replaces the current target list entirely. Passing a single target such as
     * `AttributeTargetType::MethodTarget` causes only methods to be inspected,
     * which reduces unnecessary reflection calls when the target site is known ahead
     * of time.
     *
     * @param AttributeTargetType ...$targets One or more target types to include.
     */
    public function onlyTargets(AttributeTargetType ...$targets): self
    {
        return new self(
            includeInheritance: $this->includeInheritance,
            strict: $this->strict,
            instantiate: $this->instantiate,
            useCache: $this->useCache,
            targets: $targets,
        );
    }

    /**
     * Determine whether the given target type is included in the active target list.
     *
     * Used internally by {@see \Cline\AttributeReader\Attributes::find()} to decide
     * which reflection members to traverse before performing any attribute lookups.
     *
     * @param  AttributeTargetType $type The target type to check.
     * @return bool                `true` if the type is present in the active target list.
     */
    public function includes(AttributeTargetType $type): bool
    {
        return in_array($type, $this->targets, true);
    }
}
