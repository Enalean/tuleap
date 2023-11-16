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

class SelectedDateDisplayPreferenceValidator
{
    public function validateSelectedUserPreference(string $new_relative_dates_display): bool
    {
        $allowed = [
            DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
            DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
            DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
            DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
        ];

        return in_array($new_relative_dates_display, $allowed, true);
    }
}
