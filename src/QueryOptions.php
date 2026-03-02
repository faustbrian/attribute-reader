<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader;

use ReflectionAttribute;

use function in_array;

/**
 * @psalm-immutable
 */
final readonly class QueryOptions
{
    /**
     * @param array<AttributeTargetType> $targets
     */
    private function __construct(
        public bool $includeInheritance,
        public bool $strict,
        public bool $instantiate,
        public bool $useCache,
        public array $targets,
    ) {}

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

    public function includes(AttributeTargetType $type): bool
    {
        return in_array($type, $this->targets, true);
    }

    /**
     * @param  null|class-string                                    $attribute
     * @return array{class-string, int}|array{class-string}|array{}
     */
    public function attributeArgs(?string $attribute): array
    {
        if ($attribute === null) {
            return [];
        }

        if ($this->includeInheritance === false) {
            return [$attribute];
        }

        return [$attribute, ReflectionAttribute::IS_INSTANCEOF];
    }
}
