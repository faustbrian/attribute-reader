<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\AttributeReader\Exceptions;

use Throwable;

/**
 * Marker interface implemented by every exception raised by this package.
 *
 * Catching `AttributeReaderException` is sufficient to handle all failure modes
 * from the attribute reader without depending on individual exception classes:
 *
 * ```php
 * try {
 *     $attr = Attributes::make()->onMethod(
 *         MyClass::class,
 *         'missing',
 *         Route::class,
 *         QueryOptions::default()->strict(),
 *     );
 * } catch (AttributeReaderException $e) {
 *     // Handles AttributeMethodNotFoundException and any other package exception.
 * }
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 * @see AttributeLookupException For the abstract base class used by concrete exceptions.
 */
interface AttributeReaderException extends Throwable {}
