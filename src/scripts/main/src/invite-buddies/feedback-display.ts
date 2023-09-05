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
import type { TemplateResult } from "lit";

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

interface InvitationResponse {
    readonly failures: string[];
    readonly already_project_members: User[];
    readonly known_users_added_to_project_members: User[];
    readonly known_users_not_alive: User[];
    readonly known_users_are_restricted: User[];
}

export function displaySuccess(
    emails: string[],
    response_body: InvitationResponse,
    gettext_provider: GettextProvider,
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
            successful_emails.length,
        );

        alert_success = html`
            <div class="tlp-alert-success alert-success alert">
                <p class="tlp-alert-title">${title}</p>
                <ul>
                    ${successful_emails.map(
                        (email) =>
                            html`<li data-test="success">
                                <code>${email}</code>
                            </li>`,
                    )}
                </ul>
            </div>
        `;
    }

    const alert_already_members = getAlertForUsers(
        "info",
        response_body.already_project_members,
        gettext_provider.ngettext(
            "Already project member",
            "Already project members",
            response_body.already_project_members.length,
        ),
        gettext_provider.ngettext(
            "The following user is already project member, they have been ignored:",
            "The following users are already project members, they have been ignored:",
            response_body.already_project_members.length,
        ),
        "already-member",
    );

    const alert_known_users_added = getAlertForUsers(
        "info",
        response_body.known_users_added_to_project_members,
        gettext_provider.ngettext(
            "Already registered",
            "Already registered",
            response_body.known_users_added_to_project_members.length,
        ),
        gettext_provider.ngettext(
            "The following user is already registered on the platform, they are now member of the project:",
            "The following users are already registered on the platform, they are now member of the project:",
            response_body.known_users_added_to_project_members.length,
        ),
        "known-user-added",
    );

    const alert_known_users_not_alive = getAlertForUsers(
        "danger",
        response_body.known_users_not_alive,
        gettext_provider.ngettext(
            "Not active",
            "Not active",
            response_body.known_users_not_alive.length,
        ),
        gettext_provider.ngettext(
            "The following user is not active, they cannot be added as member of the project:",
            "The following users are not active, they cannot be added as member of the project:",
            response_body.known_users_not_alive.length,
        ),
        "not-alive",
    );

    const alert_known_users_are_retricted = getAlertForUsers(
        "danger",
        response_body.known_users_are_restricted,
        gettext_provider.ngettext(
            "Restricted users",
            "Restricted users",
            response_body.known_users_are_restricted.length,
        ),
        gettext_provider.ngettext(
            "The following user is restricted, they cannot be added as member of the project because it does not accepted restricted users:",
            "The following users are restricted, they cannot be added as member of the project because it does not accepted restricted users:",
            response_body.known_users_are_restricted.length,
        ),
        "restricted",
    );

    let alert_not_sent = html``;
    if (response_body.failures.length > 0) {
        const title = gettext_provider.ngettext(
            "An invitation could not be sent",
            "Some invitations could not be sent",
            response_body.failures.length,
        );

        const description = gettext_provider.gettext(
            "An error occurred while trying to send an invitation to:",
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
                            </li>`,
                    )}
                </ul>
            </div>
        `;
    }

    render(
        html`${alert_known_users_not_alive} ${alert_known_users_are_retricted} ${alert_not_sent}
        ${alert_success} ${alert_known_users_added} ${alert_already_members}`,
        feedback,
    );
}

function getAlertForUsers(
    level: "danger" | "info",
    users: User[],
    title: string,
    description: string,
    data_test: string,
): TemplateResult {
    if (users.length <= 0) {
        return html``;
    }

    return html`
        <div class="tlp-alert-${level} alert-${level} alert">
            <p class="tlp-alert-title">${title}</p>
            <p>${description}</p>
            <ul>
                ${users.map(
                    (user) =>
                        html`<li data-test="${data_test}">
                            <code>${user.email}</code> ${user.display_name}
                        </li>`,
                )}
            </ul>
        </div>
    `;
}

function clearFeedback(): void {
    const feedback = getFeedbacksContainer();

    render(html``, feedback);
}

function getFeedbacksContainer(): HTMLElement {
    const feedback = document.getElementById("invite-buddies-modal-feedback");
    if (!feedback) {
        throw Error("Unable to find feedback container");
    }

    return feedback;
}

function isEmailASuccess(response_body: InvitationResponse, email: string): boolean {
    return (
        response_body.failures.some((failure) => email === failure) === false &&
        response_body.already_project_members.some((user) => email === user.email) === false &&
        response_body.known_users_added_to_project_members.some((user) => email === user.email) ===
            false &&
        response_body.known_users_not_alive.some((user) => email === user.email) === false &&
        response_body.known_users_are_restricted.some((user) => email === user.email) === false
    );
}
