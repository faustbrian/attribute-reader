<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use function sprintf;

final class AttributeMethodNotFoundException extends AttributeLookupException
{
    public static function forClass(string $class, string $method): self
    {
        return new self(sprintf("Method '%s' was not found on class '%s'.", $method, $class));
    }
}
