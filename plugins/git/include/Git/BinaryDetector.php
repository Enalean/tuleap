<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Git;

final class BinaryDetector
{
    private const INSPECTED_BYTES = 8000;

    /**
     * @see https://git.kernel.org/pub/scm/git/git.git/tree/xdiff-interface.c?id=v2.20.1#n187
     */
    public static function isBinary(string $data): bool
    {
        $inspected_data = \substr($data, 0, self::INSPECTED_BYTES);

        return \strpos($inspected_data, "\0") !== false;
    }
}
