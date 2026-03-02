<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use function sprintf;

final class AttributePropertyNotFoundException extends AttributeLookupException
{
    public static function forClass(string $class, string $property): self
    {
        return new self(sprintf("Property '%s' was not found on class '%s'.", $property, $class));
    }
}
