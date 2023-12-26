<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Language;

use IntlDateFormatter;

final class DateFormat
{
    public static function getYearFullMonthAndDayFormatter(\PFUser $user): IntlDateFormatter
    {
        $pattern = match ($user->getLocale()) {
            'fr_FR' => 'd MMMM yyyy',
            default => 'MMMM d, yyyy', // default to en_US
        };

        return new IntlDateFormatter(
            $user->getLocale(),
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $user->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $pattern,
        );
    }
}
