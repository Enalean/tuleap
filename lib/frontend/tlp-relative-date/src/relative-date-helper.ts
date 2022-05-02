/*
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

const PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN = "absolute_first-relative_shown";
const PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP = "relative_first-absolute_tooltip";
const PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP = "absolute_first-relative_tooltip";

export function relativeDatePreference(relative_dates_display: string): string {
    if (
        relative_dates_display === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN ||
        relative_dates_display === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP
    ) {
        return "absolute";
    }

    return "relative";
}

export function relativeDatePlacement(
    relative_dates_display: string,
    position_when_shown: "right" | "top"
): string {
    if (
        relative_dates_display === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP ||
        relative_dates_display === PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP
    ) {
        return "tooltip";
    }

    return position_when_shown;
}
