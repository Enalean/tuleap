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

export function displaySuccess(emails: string[], response_body: { failures: string[] }): void {
    const modal_body = document.getElementById("invite-buddies-modal-body");
    if (!modal_body) {
        throw Error("Unable to find body");
    }

    const modal_footer = document.getElementById("invite-buddies-modal-footer");
    if (!modal_footer) {
        throw Error("Unable to find footer");
    }

    const feedback = getFeedbacksContainer();

    const successful_emails = emails.filter((email) => !isEmailAFailure(response_body, email));

    modal_body.classList.add("invite-buddies-email-sent");
    modal_footer.classList.add("invite-buddies-email-sent");
    clearFeedback();

    if (successful_emails.length > 0) {
        const alert_success = document.createElement("div");
        alert_success.classList.add("tlp-alert-success");
        alert_success.classList.add("alert-success");
        alert_success.classList.add("alert");
        alert_success.appendChild(
            document.createTextNode(
                feedback.dataset.successFeedbackMessage + " " + successful_emails.join(", ")
            )
        );
        feedback.appendChild(alert_success);
    }

    if (response_body.failures.length > 0) {
        const alert_warning = document.createElement("div");
        alert_warning.classList.add("tlp-alert-warning");
        alert_warning.classList.add("alert-warning");
        alert_warning.classList.add("alert");
        alert_warning.appendChild(
            document.createTextNode(
                feedback.dataset.failureFeedbackMessage + " " + response_body.failures.join(", ")
            )
        );
        feedback.appendChild(alert_warning);
    }
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

function isEmailAFailure(response_body: { failures: string[] }, email: string): boolean {
    return response_body.failures.some((failure) => email === failure);
}
