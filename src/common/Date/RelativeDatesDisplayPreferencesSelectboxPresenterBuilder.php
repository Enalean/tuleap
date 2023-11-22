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

class RelativeDatesDisplayPreferencesSelectboxPresenterBuilder
{
    public function build(string $relative_dates_display): RelativeDatesDisplayPreferencesSelectboxPresenter
    {
        $is_relative_first_absolute_shown   = false;
        $is_absolute_first_relative_shown   = false;
        $is_absolute_first_relative_tooltip = false;
        $is_relative_first_absolute_tooltip = false;

        switch ($relative_dates_display) {
            case DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN:
                $is_relative_first_absolute_shown = true;
                break;
            case DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN:
                $is_absolute_first_relative_shown = true;
                break;
            case DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP:
                $is_absolute_first_relative_tooltip = true;
                break;
            case DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP:
            default:
                $is_relative_first_absolute_tooltip = true;
                break;
        }

        return new RelativeDatesDisplayPreferencesSelectboxPresenter(
            $is_relative_first_absolute_shown,
            $is_absolute_first_relative_shown,
            $is_absolute_first_relative_tooltip,
            $is_relative_first_absolute_tooltip
        );
    }
}
