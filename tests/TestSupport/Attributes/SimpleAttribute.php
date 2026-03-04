<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\TestSupport\Attributes;

use Attribute;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[Attribute(Attribute::TARGET_CLASS)]
class SimpleAttribute
{
    public function __construct(
        public readonly string $name = 'default',
    ) {}
}
