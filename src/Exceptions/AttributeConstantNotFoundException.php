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
 * Thrown in strict mode when a requested class constant does not exist on the subject class.
 *
 * Only raised when {@see \Cline\AttributeReader\QueryOptions::strict()} is active; in non-strict
 * mode the lookup silently returns `null` or `[]` instead.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AttributeConstantNotFoundException extends AttributeLookupException
{
    /**
     * Create an exception describing a missing constant on a class.
     *
     * @param class-string $class    Fully-qualified name of the class that was searched.
     * @param string       $constant Name of the constant that could not be found.
     */
    public static function forClass(string $class, string $constant): self
    {
        return new self(sprintf("Constant '%s' was not found on class '%s'.", $constant, $class));
    }
}
