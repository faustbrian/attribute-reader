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
 * @psalm-immutable
 */
final readonly class AttributeTarget
{
    /**
     * @param ReflectionClass<object>|ReflectionClassConstant|ReflectionFunction|ReflectionMethod|ReflectionParameter|ReflectionProperty $target
     */
    public function __construct(
        public object $attribute,
        public ReflectionClass|ReflectionMethod|ReflectionProperty|ReflectionClassConstant|ReflectionParameter|ReflectionFunction $target,
        public string $name,
    ) {}

    /**
     * @return array<mixed>
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
