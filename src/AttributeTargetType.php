<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader;

/**
 * Enumerates the PHP declaration sites that can be scanned for attributes.
 *
 * Used with {@see QueryOptions::onlyTargets()} to restrict an attribute query to a
 * specific subset of declaration sites, and checked internally by {@see Attributes::find()}
 * to decide which reflection members to traverse.
 *
 * ```php
 * // Scan only methods and their parameters, ignoring the class itself.
 * $options = QueryOptions::default()->onlyTargets(
 *     AttributeTargetType::MethodTarget,
 *     AttributeTargetType::ParameterTarget,
 * );
 *
 * $results = Attributes::make()->find(MyClass::class, Route::class, $options);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum AttributeTargetType: string
{
    /** Attributes declared directly on the class declaration. */
    case ClassTarget = 'class';

    /** Attributes declared on class methods. */
    case MethodTarget = 'method';

    /** Attributes declared on class properties, including promoted constructor parameters. */
    case PropertyTarget = 'property';

    /** Attributes declared on class constants. */
    case ConstantTarget = 'constant';

    /** Attributes declared on method or constructor parameters. */
    case ParameterTarget = 'parameter';
}
