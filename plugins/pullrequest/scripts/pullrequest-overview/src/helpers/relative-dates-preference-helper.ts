/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import {
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
} from "@tuleap/tlp-relative-date";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";

export const isPreferenceAbsoluteDateFirst = (
    relative_date_display_preference: RelativeDatesDisplayPreference,
): boolean =>
    relative_date_display_preference === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN ||
    relative_date_display_preference === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP;
