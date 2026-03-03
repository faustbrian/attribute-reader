<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use Throwable;

use function sprintf;

/**
 * Thrown in strict mode when a named function cannot be reflected for attribute lookup.
 *
 * Wraps the underlying `ReflectionException` as the previous exception so the full
 * cause chain is preserved. Only raised when {@see \Cline\AttributeReader\QueryOptions::strict()}
 * is active; in non-strict mode the lookup silently returns `null` or `[]` instead.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class AttributeFunctionNotFoundException extends AttributeLookupException
{
    /**
     * Create an exception describing a function that could not be reflected.
     *
     * @param string    $function Fully-qualified name of the function that could not be reflected.
     * @param Throwable $previous The `ReflectionException` that triggered this failure.
     */
    public static function forFunction(string $function, Throwable $previous): self
    {
        return new self(sprintf("Function '%s' could not be reflected.", $function), 0, $previous);
    }
}
