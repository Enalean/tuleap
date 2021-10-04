<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

final class TimezoneSwitcher
{
    /**
     * @psalm-param callable(): void $execution_context
     */
    public function setTimezoneForSpecificUserExecutionContext(\PFUser $user, callable $execution_context): void
    {
        $default_timezone = date_default_timezone_get();
        $user_timezone    = $user->getTimezone();

        try {
            if ($user_timezone) {
                date_default_timezone_set($user_timezone);
            }

            $execution_context();
        } finally {
            date_default_timezone_set($default_timezone);
        }
    }
}
