<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\TestSupport;

use Tests\TestSupport\Attributes\ConstantAttribute;
use Tests\TestSupport\Attributes\MethodAttribute;
use Tests\TestSupport\Attributes\MultiTargetAttribute;
use Tests\TestSupport\Attributes\ParameterAttribute;
use Tests\TestSupport\Attributes\PropertyAttribute;
use Tests\TestSupport\Attributes\RepeatableAttribute;
use Tests\TestSupport\Attributes\RepeatableTag;
use Tests\TestSupport\Attributes\SimpleAttribute;

/**
 * @author Brian Faust <brian@cline.sh>
 */
#[SimpleAttribute(name: 'test-class')]
#[RepeatableAttribute(tag: 'first')]
#[RepeatableAttribute(tag: 'second')]
final class TestClass
{
    #[ConstantAttribute(description: 'The active status')]
    #[RepeatableTag(tag: 'const-a')]
    #[RepeatableTag(tag: 'const-b')]
    public const string STATUS_ACTIVE = 'active';

    #[PropertyAttribute(fillable: true)]
    #[RepeatableTag(tag: 'prop-a')]
    #[RepeatableTag(tag: 'prop-b')]
    public string $name = '';

    #[PropertyAttribute(fillable: false)]
    public string $secret = '';

    #[MethodAttribute(route: '/handle')]
    #[RepeatableTag(tag: 'method-a')]
    #[RepeatableTag(tag: 'method-b')]
    public function handle(
        #[ParameterAttribute(type: 'request')]
        #[RepeatableTag(tag: 'param-a')]
        #[RepeatableTag(tag: 'param-b')]
        mixed $request,
        #[ParameterAttribute(type: 'int')]
        mixed $id,
    ): void {}

    #[MethodAttribute(route: '/process')]
    #[MultiTargetAttribute(label: 'processor')]
    public function process(): void {}

    public function plain(): void {}
}
