/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

export {};

document.addEventListener("DOMContentLoaded", () => {
    const enforce_checkbox = document.getElementById("docman-admin-filename-pattern-enforce");
    if (!(enforce_checkbox instanceof HTMLInputElement)) {
        return;
    }

    const pattern_input = document.getElementById("docman-admin-filename-pattern");
    if (!(pattern_input instanceof HTMLInputElement)) {
        return;
    }

    const mandatory_marker = document.getElementById(
        "docman-admin-filename-pattern-mandatory-marker",
    );
    if (!mandatory_marker) {
        throw Error("Cannot mark input as required if no visual indicator");
    }

    const markPatternInputAsRequiredIfPatternIsEnforced = (): void => {
        if (enforce_checkbox.checked) {
            pattern_input.required = true;
            mandatory_marker.classList.add("shown");
        } else {
            pattern_input.required = false;
            mandatory_marker.classList.remove("shown");
        }
    };

    enforce_checkbox.addEventListener("change", markPatternInputAsRequiredIfPatternIsEnforced);
    markPatternInputAsRequiredIfPatternIsEnforced();
});
