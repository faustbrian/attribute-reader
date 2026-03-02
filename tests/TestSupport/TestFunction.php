<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\TestSupport;

use Tests\TestSupport\Attributes\MultiTargetAttribute;

#[MultiTargetAttribute(label: 'standalone')]
function testFunction(): void {}
