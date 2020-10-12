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
let ticking = false;

export function setupPinnedButton(document: Document, window: Window): void {
    const submit_section = document.getElementById(SUBMIT_SECTION_ID);
    if (!(submit_section instanceof HTMLElement)) {
        throw new Error(`Section #${SUBMIT_SECTION_ID} is missing from the DOM`);
    }

    const submit_button = document.getElementById(FORM_SUBMIT_BUTTON_ID);
    if (!(submit_button instanceof HTMLButtonElement)) {
        throw new Error(`Submit button #${FORM_SUBMIT_BUTTON_ID} is missing from the DOM`);
    }

    document.addEventListener("scroll", () => observeSubmitButton(submit_button, submit_section));
    window.addEventListener("resize", () => observeSubmitButton(submit_button, submit_section));

    for (const background of document.querySelectorAll(".project-admin-background-radio")) {
        background.addEventListener("change", () => {
            is_submit_button_pinneable = true;
            markSubmitSectionAsPinned(submit_button, submit_section);
        });
    }
}

function observeSubmitButton(submit_button: HTMLButtonElement, submit_section: HTMLElement): void {
    if (!ticking) {
        window.requestAnimationFrame(() => {
            if (is_submit_button_pinneable) {
                markSubmitSectionAsPinned(submit_button, submit_section);
            }
            ticking = false;
        });

        ticking = true;
    }
}

function isSubmitButtonInViewport(submit_button: HTMLButtonElement): boolean {
    return submit_button.getBoundingClientRect().bottom < window.innerHeight;
}

function markSubmitSectionAsPinned(
    submit_button: HTMLButtonElement,
    submit_section: HTMLElement
): void {
    if (!isSubmitButtonInViewport(submit_button)) {
        submit_section.classList.add("pinned");
    }
}
