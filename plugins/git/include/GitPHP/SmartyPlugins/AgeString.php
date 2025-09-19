<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP\SmartyPlugins;

final readonly class AgeString
{
    public const string MODIFIER = 'agestring';

    /**
     * Smarty modifier to turn an age in seconds into a
     * human-readable string
     *
     * @param int $age age in seconds
     * @return string human-readable string
     */
    public static function callback(int $age): string
    {
        if ($age > 60 * 60 * 24 * 365 * 2) {
            $years = (int) ($age / 60 / 60 / 24 / 365);
            return sprintf(dngettext('gitphp', '%1$d year ago', '%1$d years ago', $years), $years);
        } elseif ($age > 60 * 60 * 24 * (365 / 12) * 2) {
            $months = (int) ($age / 60 / 60 / 24 / (365 / 12));
            return sprintf(dngettext('gitphp', '%1$d month ago', '%1$d months ago', $months), $months);
        } elseif ($age > 60 * 60 * 24 * 7 * 2) {
            $weeks = (int) ($age / 60 / 60 / 24 / 7);
            return sprintf(dngettext('gitphp', '%1$d week ago', '%1$d weeks ago', $weeks), $weeks);
        } elseif ($age > 60 * 60 * 24 * 2) {
            $days = (int) ($age / 60 / 60 / 24);
            return sprintf(dngettext('gitphp', '%1$d day ago', '%1$d days ago', $days), $days);
        } elseif ($age > 60 * 60 * 2) {
            $hours = (int) ($age / 60 / 60);
            return sprintf(dngettext('gitphp', '%1$d hour ago', '%1$d hours ago', $hours), $hours);
        } elseif ($age > 60 * 2) {
            $min = (int) ($age / 60);
            return sprintf(dngettext('gitphp', '%1$d min ago', '%1$d min ago', $min), $min);
        } elseif ($age > 2) {
            $sec = $age;
            return sprintf(dngettext('gitphp', '%1$d sec ago', '%1$d sec ago', $sec), $sec);
        }

        return dgettext('gitphp', 'right now');
    }
}
