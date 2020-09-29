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

import { post } from "../../themes/tlp/src/js/fetch-wrapper";
import { activateSpinner, deactivateSpinner } from "./spinner-activation";
import { handleError } from "./handle-error";
import { displaySuccess } from "./feedback-display";

export function initNotificationsOnFormSubmit(): void {
    const form = document.getElementById("invite-buddies-modal");
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        sendNotifications(form);
    });
}

export async function sendNotifications(form: HTMLFormElement): Promise<void> {
    const email_input = form.querySelector("input[name=invite_buddies_email]");
    if (!(email_input instanceof HTMLInputElement)) {
        throw Error("Unable to find email field");
    }

    const message_input = form.querySelector("textarea[name=invite_buddies_message]");
    if (!(message_input instanceof HTMLTextAreaElement)) {
        throw Error("Unable to find message field");
    }

    const button = form.querySelector("button[type=submit]");
    if (!(button instanceof HTMLButtonElement)) {
        throw Error("Unable to find submit button");
    }

    const icon = form.querySelector("button[type=submit] > .tlp-button-icon");
    try {
        activateSpinner(icon);
        button.disabled = true;
        const emails = getEmails(email_input);
        const custom_message = message_input.value;
        const response = await post(`/api/v1/invitations`, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                emails,
                custom_message,
            }),
        });

        const response_body = await response.json();
        displaySuccess(emails, response_body);
    } catch (rest_error) {
        await handleError(rest_error);
    } finally {
        deactivateSpinner(icon);
        button.disabled = false;
    }
}

function getEmails(input: HTMLInputElement): string[] {
    return input.value.split(/,\s*/);
}
