<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Disposable;

final class Dispose
{
    /**
     * Intentionally private. This class should not have instances.
     */
    private function __construct()
    {
    }

    /**
     * using calls `$fn` with `$disposable` as its first argument, and at
     * the end of the function, it calls `$disposable->dispose()`.
     * It returns the return value of `$fn`.
     * If an exception or an error is thrown in `$fn`, it will still call `$disposable->dispose()`
     *
     * @template TDisposable of Disposable
     * @template TReturn
     * @param TDisposable                    $disposable
     * @param callable(TDisposable): TReturn $fn
     * @return TReturn
     */
    public static function using(Disposable $disposable, callable $fn)
    {
        try {
            return $fn($disposable);
        } finally {
            $disposable->dispose();
        }
    }
}
