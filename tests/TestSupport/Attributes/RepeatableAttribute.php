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
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class RepeatableAttribute
{
    public function __construct(
        public string $tag = '',
    ) {}
}
