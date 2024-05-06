<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap;

use DateTimeZone;
use Exception;
use ForgeConfig;
use PFUser;
use Tuleap\Config\ConfigurationVariables;

final class TimezoneRetriever
{
    /**
     * @psalm-return non-empty-string
     */
    public static function getServerTimezone(): string
    {
        return ForgeConfig::get(ConfigurationVariables::SERVER_TIMEZONE);
    }

    /**
     * @psalm-return non-empty-string
     */
    public static function getUserTimezone(PFUser $user): string
    {
        $timezone = '';
        if (! $user->isAnonymous()) {
            $timezone = $user->getTimezone() ?? '';
        }
        if ($timezone === '') {
            return self::getServerTimezone();
        }
        try {
            new DateTimeZone($timezone);
        } catch (Exception) {
            $timezone = self::getServerTimezone();
        }
        return $timezone;
    }
}
