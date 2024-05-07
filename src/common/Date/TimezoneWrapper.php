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

namespace Tuleap\Date;

final class TimezoneWrapper
{
    private function __construct()
    {
    }

    /**
     * @template T
     * @param non-empty-string $timezone
     * @param callable(): T $callback
     * @return T
     */
    public static function wrapTimezone(
        string $timezone,
        callable $callback,
    ): mixed {
        $current_timezone = date_default_timezone_get();
        date_default_timezone_set($timezone);

        try {
            $return_value = $callback();
        } finally {
            date_default_timezone_set($current_timezone);
        }

        return $return_value;
    }
}
