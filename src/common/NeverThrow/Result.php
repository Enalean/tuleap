<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\NeverThrow;

final class Result
{
    /**
     * Intentionally private. This class should not have instances.
     */
    private function __construct()
    {
    }

    /**
     * ok returns a new 'Ok' variant of Result wrapping $value.
     * @template TValue
     * @param TValue $value The wrapped value. Can be anything.
     * @return Ok<TValue>
     * @psalm-pure
     */
    public static function ok(mixed $value): Ok
    {
        return new Ok($value);
    }

    /**
     * err returns a new 'Err' variant of Result wrapping $error.
     * @template TError
     * @param TError $error The wrapper error. Can be anything.
     * @return Err<TError>
     * @psalm-pure
     */
    public static function err(mixed $error): Err
    {
        return new Err($error);
    }

    /**
     * isOk returns true if $result is an `Ok` variant
     * @psalm-assert-if-true Ok $result
     * @psalm-pure
     */
    public static function isOk(Ok|Err $result): bool
    {
        return $result instanceof Ok;
    }

    /**
     * isErr returns true if $result is an `Err` variant
     * @psalm-assert-if-true Err $result
     * @psalm-pure
     */
    public static function isErr(Ok|Err $result): bool
    {
        return $result instanceof Err;
    }
}
