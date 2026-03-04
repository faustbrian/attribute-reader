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
 * Thrown in strict mode when a requested method does not exist on the subject class.
 *
 * Also raised when a parameter lookup targets a method that does not exist, since the
 * parameter cannot be located without first resolving the method. Only raised when
 * {@see \Cline\AttributeReader\QueryOptions::strict()} is active; in non-strict mode the
 * lookup silently returns `null` or `[]` instead.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AttributeMethodNotFoundException extends AttributeLookupException
{
    /**
     * Create an exception describing a missing method on a class.
     *
     * @param class-string $class  Fully-qualified name of the class that was searched.
     * @param string       $method Name of the method that could not be found.
     */
    public static function forClass(string $class, string $method): self
    {
        return new self(sprintf("Method '%s' was not found on class '%s'.", $method, $class));
    }
}
