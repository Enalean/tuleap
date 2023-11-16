<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

class DefaultRelativeDatesDisplayPreferenceRetriever
{
    public const DEFAULT_RELATIVE_DATES_DISPLAY = 'default-relative-dates-display';

    public static function retrieveDefaultValue(): string
    {
        $preference_defined_by_site_administrator = \ForgeConfig::get(self::DEFAULT_RELATIVE_DATES_DISPLAY);

        if (! $preference_defined_by_site_administrator) {
            return DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP;
        }

        return (string) $preference_defined_by_site_administrator;
    }

    public static function getDefaultPlacementAndPreference(string $position_from_context): DefaultRelativeDatesDisplayPreference
    {
        $value = self::retrieveDefaultValue();

        switch ($value) {
            case DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN:
                return new DefaultRelativeDatesDisplayPreference('absolute', $position_from_context);
            case DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP:
                return new DefaultRelativeDatesDisplayPreference('absolute', 'tooltip');
            case DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN:
                return new DefaultRelativeDatesDisplayPreference('relative', $position_from_context);
            case DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP:
            default:
                return new DefaultRelativeDatesDisplayPreference('relative', 'tooltip');
        }
    }
}
