<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use function sprintf;

final class AttributeParameterNotFoundException extends AttributeLookupException
{
    public static function forMethod(string $class, string $method, string $parameter): self
    {
        return new self(sprintf("Parameter '%s' was not found on method '%s::%s'.", $parameter, $class, $method));
    }
}
