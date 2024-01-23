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

/**
 * I hold a resource that needs to be cleaned-up after using it.
 * For example, an open file descriptor, or some database data for integration tests.
 */
interface Disposable
{
    /**
     * Clean-up method, will be called at the end of `Dispose::using()`.
     * `dispose()` should be idempotent (can be called more than once).
     * @see Dispose::using()
     */
    public function dispose(): void;
}
