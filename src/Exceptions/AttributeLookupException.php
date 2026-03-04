<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use RuntimeException;

/**
 * Abstract base class for exceptions raised when a requested attribute target cannot be located.
 *
 * All concrete `*NotFoundException` classes in this package extend this type, so it can be
 * caught as a single, specific exception family distinct from other `RuntimeException` subtypes:
 *
 * ```php
 * try {
 *     $attr = $reader->onProperty(MyClass::class, 'nonExistent', options: QueryOptions::default()->strict());
 * } catch (AttributeLookupException $e) {
 *     // Covers missing methods, properties, constants, parameters, and functions.
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @see AttributeReaderException The top-level marker interface for all package exceptions.
 */
abstract class AttributeLookupException extends RuntimeException implements AttributeReaderException {}
