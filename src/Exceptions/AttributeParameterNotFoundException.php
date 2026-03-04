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
 * Thrown in strict mode when a requested parameter does not exist on the subject method.
 *
 * Only raised after the parent method has been confirmed to exist — if the method itself is
 * missing, an {@see AttributeMethodNotFoundException} is thrown first. Only raised when
 * {@see \Cline\AttributeReader\QueryOptions::strict()} is active; in non-strict mode the
 * lookup silently returns `null` or `[]` instead.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AttributeParameterNotFoundException extends AttributeLookupException
{
    /**
     * Create an exception describing a missing parameter on a class method.
     *
     * @param class-string $class     Fully-qualified name of the class that owns the method.
     * @param string       $method    Name of the method that was searched.
     * @param string       $parameter Name of the parameter that could not be found (without the leading `$`).
     */
    public static function forMethod(string $class, string $method, string $parameter): self
    {
        return new self(sprintf("Parameter '%s' was not found on method '%s::%s'.", $parameter, $class, $method));
    }
}
