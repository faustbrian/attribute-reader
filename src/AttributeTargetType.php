<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader;

enum AttributeTargetType: string
{
    case ClassTarget = 'class';
    case MethodTarget = 'method';
    case PropertyTarget = 'property';
    case ConstantTarget = 'constant';
    case ParameterTarget = 'parameter';
}
