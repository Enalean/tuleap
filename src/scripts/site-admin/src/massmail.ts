/**
 * Copyright (c) Enalean SAS - 2016 - Present. All rights reserved
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

import CKEDITOR from "ckeditor4";
import { config } from "@tuleap/ckeditor-config";

import { autocomplete_users_for_select2 as autocomplete } from "@tuleap/autocomplete-for-select2";
import { createModal } from "@tuleap/tlp-modal";

const warning_element = document.getElementById("massmail-warning"),
    destination_element = document.getElementById("massmail-destination"),
    submit_button = document.getElementById("massmail-submit"),
    preview_button = document.getElementById("massmail-preview-destination-submit"),
    preview_feedback = document.getElementById("massmail-preview-feedback"),
    confirm_button = document.getElementById("massmail-confirm-sending"),
    confirm_element = document.getElementById("massmail-modal-warning");
if (
    !warning_element ||
    !destination_element ||
    !preview_button ||
    !preview_feedback ||
    !confirm_button ||
    !confirm_element
) {
    throw new Error("Some mass-mail dom element has not be found in DOM");
}

if (!(preview_button instanceof HTMLButtonElement)) {
    throw new Error("preview_button is not an input button");
}

const form = preview_button.form;
if (!form) {
    throw new Error("form preview is not found in dom");
}

document.addEventListener("DOMContentLoaded", () => {
    let ckeditor: CKEDITOR.editor | null = null;
    let preview_timeout: number | undefined;

    const confirm_modal = createModal(confirm_element);

    changeWarningTextAccordinglyToDestination();
    destination_element.addEventListener("change", changeWarningTextAccordinglyToDestination);
    preview_button.addEventListener("click", sendAPreview);
    form.addEventListener("submit", openConfirmationModal);
    confirm_button.addEventListener("click", confirmationSubmitsTheForm);
    initHTMLEditor();
    initSelect2();

    function changeWarningTextAccordinglyToDestination(): void {
        if (
            !warning_element ||
            !destination_element ||
            !submit_button ||
            !(submit_button instanceof HTMLButtonElement)
        ) {
            return;
        }
        if (!(destination_element instanceof HTMLSelectElement)) {
            return;
        }

        const warning = destination_element[destination_element.selectedIndex].dataset.warning;
        if (!warning) {
            return;
        }
        warning_element.innerText = warning;

        const number_of_users =
            destination_element[destination_element.selectedIndex].dataset.nbUsers;
        if (!number_of_users) {
            return;
        }
        submit_button.disabled = parseInt(number_of_users, 10) < 1;
    }

    function openConfirmationModal(event: Event): void {
        event.preventDefault();
        confirm_modal.show();
    }

    function confirmationSubmitsTheForm(): void {
        if (!form) {
            return;
        }
        form.submit();
    }

    function initHTMLEditor(): void {
        ckeditor = CKEDITOR.replace("mail_message", config);
    }

    function initSelect2(): void {
        const preview = document.getElementById("massmail-preview-destination");
        if (!preview) {
            return;
        }

        autocomplete(preview, {});
    }

    function sendAPreview(): void {
        if (!ckeditor) {
            return;
        }
        const editor = document.getElementById("mail_message");
        if (!editor || !(editor instanceof HTMLTextAreaElement)) {
            throw new Error("mail_message editor dom element was not found");
        }
        editor.value = ckeditor.getData();
        if (!form || !preview_feedback) {
            return;
        }
        const data = new FormData(form);

        clearFeedback();

        data.append("destination", "preview");

        const req = new XMLHttpRequest();
        req.open("POST", form.action);
        req.onload = (): void => {
            previewResponseHandler(req);
        };

        req.send(data);
    }

    function previewResponseHandler(req: XMLHttpRequest): void {
        let response;

        try {
            response = JSON.parse(req.responseText);
        } catch (e) {
            // ignore SyntaxError
        }

        if (!preview_feedback) {
            throw new Error("Feedback element is not deined");
        }

        if (!response) {
            preview_feedback.classList.add("tlp-alert-danger");
            preview_feedback.innerText = "Something is wrong with your request";
        } else if (!response.success) {
            preview_feedback.classList.add("tlp-alert-danger");
            preview_feedback.innerText = response.message;
        } else {
            preview_feedback.classList.add("tlp-alert-success");
            preview_feedback.innerText = response.message;
        }

        preview_timeout = window.setTimeout(clearFeedback, 5000);
    }

    function clearFeedback(): void {
        if (!preview_feedback) {
            return;
        }
        preview_feedback.innerHTML = "";
        preview_feedback.classList.remove("tlp-alert-success");
        preview_feedback.classList.remove("tlp-alert-danger");

        window.clearTimeout(preview_timeout);
        preview_timeout = undefined;
    }
});
