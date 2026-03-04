<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use function sprintf;

/**
 * Thrown in strict mode when a requested property does not exist on the subject class.
 *
 * Only raised when {@see \Cline\AttributeReader\QueryOptions::strict()} is active; in non-strict
 * mode the lookup silently returns `null` or `[]` instead.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AttributePropertyNotFoundException extends AttributeLookupException
{
    /**
     * Create an exception describing a missing property on a class.
     *
     * @param class-string $class    Fully-qualified name of the class that was searched.
     * @param string       $property Name of the property that could not be found (without the leading `$`).
     */
    public static function forClass(string $class, string $property): self
    {
        return new self(sprintf("Property '%s' was not found on class '%s'.", $property, $class));
    }
}
