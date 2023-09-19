/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

export {};

document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.getElementById("toggle-cross-referenced-by");
    const cross_referenced_by_section = document.getElementById(
        "tracker-cross-referenced-by-section",
    );

    if (!(toggle instanceof HTMLInputElement) || !cross_referenced_by_section) {
        return;
    }

    toggle.checked = false;
    adjustVisibilityCrossReferences(toggle, cross_referenced_by_section);

    toggle.addEventListener("change", () => {
        adjustVisibilityCrossReferences(toggle, cross_referenced_by_section);
    });

    function adjustVisibilityCrossReferences(toggle: HTMLInputElement, section: HTMLElement): void {
        if (toggle.checked) {
            section.classList.remove("tracker-cross-reference-hide-referenced-by");
        } else {
            section.classList.add("tracker-cross-reference-hide-referenced-by");
        }
    }
});
