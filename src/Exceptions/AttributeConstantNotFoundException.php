<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use function sprintf;

final class AttributeConstantNotFoundException extends AttributeLookupException
{
    public static function forClass(string $class, string $constant): self
    {
        return new self(sprintf("Constant '%s' was not found on class '%s'.", $constant, $class));
    }
}
