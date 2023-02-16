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

import type { GettextProvider } from "@tuleap/gettext";
import { html, render } from "lit/html.js";

export function initFeedbacks(): void {
    clearFeedback();
    const modal_body = document.getElementById("invite-buddies-modal-body");
    if (modal_body) {
        modal_body.classList.remove("invite-buddies-email-sent");
    }

    const modal_footer = document.getElementById("invite-buddies-modal-footer");
    if (modal_footer) {
        modal_footer.classList.remove("invite-buddies-email-sent");
    }
}

export function displayError(message: string): void {
    const feedback = getFeedbacksContainer();

    clearFeedback();
    const alert = document.createElement("div");
    alert.classList.add("tlp-alert-danger");
    alert.classList.add("alert-error");
    alert.classList.add("alert");
    alert.innerText = message;

    feedback.appendChild(alert);
}

interface User {
    readonly email: string;
    readonly display_name: string;
}

export function displaySuccess(
    emails: string[],
    response_body: { failures: string[]; already_project_members: User[] },
    gettext_provider: GettextProvider
): void {
    const modal_body = document.getElementById("invite-buddies-modal-body");
    if (!modal_body) {
        throw Error("Unable to find body");
    }

    const modal_footer = document.getElementById("invite-buddies-modal-footer");
    if (!modal_footer) {
        throw Error("Unable to find footer");
    }

    const feedback = getFeedbacksContainer();

    const successful_emails = emails.filter((email) => isEmailASuccess(response_body, email));

    modal_body.classList.add("invite-buddies-email-sent");
    modal_footer.classList.add("invite-buddies-email-sent");
    clearFeedback();

    let alert_success = html``;
    if (successful_emails.length > 0) {
        const title = gettext_provider.ngettext(
            "Invitation successfully sent",
            "Invitations successfully sent",
            successful_emails.length
        );

        alert_success = html`
            <div class="tlp-alert-success alert-success alert">
                <p class="tlp-alert-title">${title}</p>
                <ul>
                    ${successful_emails.map(
                        (email) =>
                            html`<li data-test="success">
                                <code>${email}</code>
                            </li>`
                    )}
                </ul>
            </div>
        `;
    }

    let alert_already_members = html``;
    if (response_body.already_project_members.length > 0) {
        const title = gettext_provider.ngettext(
            "Already project member",
            "Already project members",
            response_body.already_project_members.length
        );

        const description = gettext_provider.ngettext(
            "The following user is already project member, they have been ignored:",
            "The following users are already project members, they have been ignored:",
            response_body.already_project_members.length
        );

        alert_already_members = html`
            <div class="tlp-alert-info alert-info alert">
                <p class="tlp-alert-title">${title}</p>
                <p>${description}</p>
                <ul>
                    ${response_body.already_project_members.map(
                        (user) =>
                            html`<li data-test="already-member">
                                <code>${user.email}</code> ${user.display_name}
                            </li>`
                    )}
                </ul>
            </div>
        `;
    }

    let alert_not_sent = html``;
    if (response_body.failures.length > 0) {
        const title = gettext_provider.ngettext(
            "An invitation could not be sent",
            "Some invitations could not be sent",
            response_body.failures.length
        );

        const description = gettext_provider.gettext(
            "An error occurred while trying to send an invitation to:"
        );

        alert_not_sent = html`
            <div class="tlp-alert-warning alert-warning alert">
                <p class="tlp-alert-title">${title}</p>
                <p>${description}</p>
                <ul>
                    ${response_body.failures.map(
                        (email) =>
                            html`<li data-test="could-not-be-sent">
                                <code>${email}</code>
                            </li>`
                    )}
                </ul>
            </div>
        `;
    }

    render(html`${alert_not_sent} ${alert_success} ${alert_already_members}`, feedback);
}

function clearFeedback(): void {
    const feedback = getFeedbacksContainer();

    feedback.innerHTML = "";
}

function getFeedbacksContainer(): HTMLElement {
    const feedback = document.getElementById("invite-buddies-modal-feedback");
    if (!feedback) {
        throw Error("Unable to find feedback container");
    }

    return feedback;
}

function isEmailASuccess(
    response_body: { failures: string[]; already_project_members: User[] },
    email: string
): boolean {
    return (
        response_body.failures.some((failure) => email === failure) === false &&
        response_body.already_project_members.some((user) => email === user.email) === false
    );
}
