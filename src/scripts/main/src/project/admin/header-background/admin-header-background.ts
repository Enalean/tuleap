/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

const FORM_ID = "form-header-background";
const FORM_ERROR_ID = "project-admin-background-error";
const FORM_SUCCESS_ID = "project-admin-background-success";
const FORM_SUBMIT_BUTTON_ID = "project-admin-background-submit-button";
const FORM_SUBMIT_BUTTON_ICON_ID = "project-admin-background-submit-button-icon";
const NO_BACKGROUND_IDENTIFIER = "0";
const LOCATION_HASH_SUCCESS = "#header-background-change-success";
const CLASS_HIDE_FEEDBACK = "project-admin-background-feedback-hidden";

import { del, put } from "@tuleap/tlp-fetch";

export function setupFormSubmission(mount_point: Document, location: Location): void {
    const form = mount_point.getElementById(FORM_ID);
    if (!(form instanceof HTMLFormElement)) {
        throw new Error(`Form #${FORM_ID} is missing from the DOM`);
    }

    const form_submission_error = mount_point.getElementById(FORM_ERROR_ID);
    if (form_submission_error === null) {
        throw new Error(`Error element #${FORM_ERROR_ID} is missing from the DOM`);
    }

    const form_submission_success = mount_point.getElementById(FORM_SUCCESS_ID);
    if (form_submission_success === null) {
        throw new Error(`Success element #${FORM_SUCCESS_ID} is missing from the DOM`);
    }

    const form_submit_button = mount_point.getElementById(FORM_SUBMIT_BUTTON_ID);
    if (!(form_submit_button instanceof HTMLButtonElement)) {
        throw new Error(`Submit button #${FORM_SUBMIT_BUTTON_ID} is missing from the DOM`);
    }

    const form_submit_button_icon = mount_point.getElementById(FORM_SUBMIT_BUTTON_ICON_ID);
    if (form_submit_button_icon === null) {
        throw new Error(
            `Submit button icon #${FORM_SUBMIT_BUTTON_ICON_ID} is missing from the DOM`,
        );
    }

    if (location.hash === LOCATION_HASH_SUCCESS) {
        form_submission_success.classList.remove(CLASS_HIDE_FEEDBACK);
        location.hash = "";
    }

    form.addEventListener("submit", async (event: Event) => {
        event.preventDefault();

        const form_data = new FormData(form);

        const project_id = String(form_data.get("project-id"));
        const new_background_identifier = String(form_data.get("new-background"));

        const update_header_background_url = `/api/v1/projects/${encodeURI(
            project_id,
        )}/header_background`;

        disableSubmitButton(form_submit_button, form_submit_button_icon);
        try {
            if (new_background_identifier === NO_BACKGROUND_IDENTIFIER) {
                await del(update_header_background_url);
            } else {
                await put(update_header_background_url, {
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ identifier: new_background_identifier }),
                });
            }
        } catch (e) {
            form_submission_success.classList.add(CLASS_HIDE_FEEDBACK);
            form_submission_error.classList.remove(CLASS_HIDE_FEEDBACK);
            form_submission_error.scrollIntoView();
            throw e;
        } finally {
            enableSubmitButton(form_submit_button, form_submit_button_icon);
        }
        location.hash = LOCATION_HASH_SUCCESS;
        location.reload();
    });
}

function disableSubmitButton(
    submit_button: HTMLButtonElement,
    submit_button_icon: HTMLElement,
): void {
    submit_button.disabled = true;
    submit_button.classList.add("tlp-button-disabled");
    submit_button_icon.classList.remove("far", "fa-save");
    submit_button_icon.classList.add("fas", "fa-spin", "fa-circle-notch");
}
function enableSubmitButton(
    submit_button: HTMLButtonElement,
    submit_button_icon: HTMLElement,
): void {
    submit_button.disabled = false;
    submit_button.classList.remove("tlp-button-disabled");
    submit_button_icon.classList.add("far", "fa-save");
    submit_button_icon.classList.remove("fas", "fa-spin", "fa-circle-notch");
}
