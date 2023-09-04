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

import type {
    RelativeDateElement,
    RelativeDatesDisplayPreference,
} from "@tuleap/tlp-relative-date";
import {
    relativeDatePlacement,
    relativeDatePreference,
    PREFERENCE_CHOICES,
} from "@tuleap/tlp-relative-date";

document.addEventListener("DOMContentLoaded", () => {
    listenToPreferenceChange(document);
});

export function listenToPreferenceChange(doc: Document): void {
    const date_display_preference_select = doc.getElementById("relative-dates-display");

    if (!(date_display_preference_select instanceof HTMLSelectElement)) {
        throw new Error("Unable to find the relative dates display preferences <select>");
    }

    const tlp_relative_dates_component = doc.querySelector("tlp-relative-date");

    if (!isTlpRelativeDate(tlp_relative_dates_component)) {
        throw new Error("Unable to find the <tlp-relative-date> component");
    }

    applyDatesDisplayPreference(date_display_preference_select, tlp_relative_dates_component);
    date_display_preference_select.addEventListener("change", () =>
        applyDatesDisplayPreference(date_display_preference_select, tlp_relative_dates_component),
    );
}

const isValidPreference = (preference: string): preference is RelativeDatesDisplayPreference =>
    PREFERENCE_CHOICES.includes(preference);

function applyDatesDisplayPreference(
    date_display_preference_select: HTMLSelectElement,
    tlp_relative_dates_component: RelativeDateElement,
): void {
    const preference = date_display_preference_select.value;
    if (!isValidPreference(preference)) {
        return;
    }

    tlp_relative_dates_component.placement = relativeDatePlacement(preference, "right");
    tlp_relative_dates_component.preference = relativeDatePreference(preference);
}

function isTlpRelativeDate(element: Element | null): element is RelativeDateElement {
    if (element === null) {
        return false;
    }

    return "placement" in element && "preference" in element;
}
