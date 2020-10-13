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

const SUBMIT_SECTION_ID = "project-admin-background-submit-section";
const FORM_SUBMIT_BUTTON_ID = "project-admin-background-submit-button";

let is_submit_button_pinneable = false;
let is_submit_button_visible = false;
let observer: IntersectionObserver | null = null;

export function setupPinnedButton(document: Document): void {
    const submit_section = document.getElementById(SUBMIT_SECTION_ID);
    if (!(submit_section instanceof HTMLElement)) {
        throw new Error(`Section #${SUBMIT_SECTION_ID} is missing from the DOM`);
    }

    const submit_button = document.getElementById(FORM_SUBMIT_BUTTON_ID);
    if (!(submit_button instanceof HTMLButtonElement)) {
        throw new Error(`Submit button #${FORM_SUBMIT_BUTTON_ID} is missing from the DOM`);
    }

    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.target.id !== FORM_SUBMIT_BUTTON_ID) {
                return;
            }

            is_submit_button_visible = entry.isIntersecting;
            if (is_submit_button_pinneable) {
                markSubmitSectionAsPinned(submit_button, submit_section);
            }
        });
    });
    observer.observe(submit_button);

    for (const background of document.querySelectorAll(".project-admin-background-radio")) {
        background.addEventListener("change", () => {
            is_submit_button_pinneable = true;
            markSubmitSectionAsPinned(submit_button, submit_section);
        });
    }
}

function markSubmitSectionAsPinned(button: HTMLButtonElement, section: HTMLElement): void {
    if (!is_submit_button_visible) {
        section.classList.add("pinned");
        observer?.unobserve(button);
    }
}
