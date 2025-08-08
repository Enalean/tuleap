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

import type {
    FirstDateShown,
    OTHER_PLACEMENT_RIGHT,
    OTHER_PLACEMENT_TOP,
    OtherDatePlacement,
} from "./relative-date-element";
import {
    OTHER_PLACEMENT_TOOLTIP,
    SHOW_ABSOLUTE_DATE,
    SHOW_RELATIVE_DATE,
} from "./relative-date-element";

export const PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN = "absolute_first-relative_shown";
export const PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP = "absolute_first-relative_tooltip";
export const PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN = "relative_first-absolute_shown";
export const PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP = "relative_first-absolute_tooltip";

export type RelativeDatesDisplayPreference =
    | typeof PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN
    | typeof PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP
    | typeof PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN
    | typeof PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP;

export const PREFERENCE_CHOICES = [
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
];

export function relativeDatePreference(
    relative_dates_display: RelativeDatesDisplayPreference,
): FirstDateShown {
    if (
        relative_dates_display === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN ||
        relative_dates_display === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP
    ) {
        return SHOW_ABSOLUTE_DATE;
    }

    return SHOW_RELATIVE_DATE;
}

export function relativeDatePlacement(
    relative_dates_display: RelativeDatesDisplayPreference,
    position_when_shown: typeof OTHER_PLACEMENT_RIGHT | typeof OTHER_PLACEMENT_TOP,
): OtherDatePlacement {
    if (
        relative_dates_display === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP ||
        relative_dates_display === PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP
    ) {
        return OTHER_PLACEMENT_TOOLTIP;
    }

    return position_when_shown;
}

const isValidPreference = (
    preference: string | null,
): preference is RelativeDatesDisplayPreference => {
    return PREFERENCE_CHOICES.includes(preference ?? "");
};

export const getRelativeDateUserPreferenceOrThrow = (
    element: Element,
    attribute_name: string,
): RelativeDatesDisplayPreference => {
    const preference = element.getAttribute(attribute_name);
    if (!isValidPreference(preference)) {
        throw Error("Could not read relative date preference from given element");
    }
    return preference;
};
