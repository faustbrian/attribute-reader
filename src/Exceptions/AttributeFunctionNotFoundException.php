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

final class AttributeFunctionNotFoundException extends AttributeLookupException
{
    public static function forFunction(string $function, Throwable $previous): self
    {
        return new self(sprintf("Function '%s' could not be reflected.", $function), 0, $previous);
    }
}
