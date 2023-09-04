/**
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
import type { post } from "@tuleap/tlp-fetch";

export function contactSupportModalShown(mount_point: Document, tlp_post: typeof post): () => void {
    return (): void => {
        const contact_support_modal_forms = mount_point.querySelectorAll(
            ".contact-support-modal-form",
        );

        for (const contact_support_modal_form of contact_support_modal_forms) {
            if (!(contact_support_modal_form instanceof HTMLFormElement)) {
                throw new Error(
                    "An element has the class .contact-support-modal-form but is not a form",
                );
            }
            contact_support_modal_form.addEventListener(
                "submit",
                async (event: Event): Promise<void> => {
                    event.preventDefault();

                    const contact_support_modal_submit = contact_support_modal_form.querySelector(
                            ".contact-support-modal-submit",
                        ),
                        contact_support_modal_success_message =
                            contact_support_modal_form.querySelector(
                                ".contact-support-modal-success-message",
                            ),
                        contact_support_modal_error_message =
                            contact_support_modal_form.querySelector(
                                ".contact-support-modal-error-message",
                            );

                    if (!(contact_support_modal_submit instanceof HTMLButtonElement)) {
                        throw new Error("Contact support modal submit button not found");
                    }
                    if (
                        !(contact_support_modal_success_message instanceof Element) ||
                        !(contact_support_modal_error_message instanceof Element)
                    ) {
                        throw new Error(
                            "Success or error message of the contact support modal is missing",
                        );
                    }

                    switchSubmitButtonToSendingState(contact_support_modal_submit);

                    try {
                        await tlp_post(contact_support_modal_form.action, {
                            body: new FormData(contact_support_modal_form),
                        });
                    } catch (error) {
                        switchSubmitButtonToNormalState(contact_support_modal_submit);
                        showErrorMessage(contact_support_modal_error_message);
                        return;
                    }

                    hideErrorMessage(contact_support_modal_error_message);
                    switchSubmitButtonToThankYouState(contact_support_modal_submit);
                    showSuccessMessage(contact_support_modal_success_message);

                    setTimeout(function () {
                        contact_support_modal_form.reset();
                        switchSubmitButtonToNormalState(contact_support_modal_submit);
                        hideSuccessMessage(contact_support_modal_success_message);
                    }, 5000);
                },
            );
        }
    };
}

function switchSubmitButtonToSendingState(contact_support_modal_submit: HTMLButtonElement): void {
    contact_support_modal_submit.disabled = true;
    contact_support_modal_submit.classList.remove("thank-you");
    contact_support_modal_submit.classList.add("sending");
}

function switchSubmitButtonToThankYouState(contact_support_modal_submit: HTMLButtonElement): void {
    contact_support_modal_submit.disabled = true;
    contact_support_modal_submit.classList.remove("sending");
    contact_support_modal_submit.classList.add("thank-you");
}

function switchSubmitButtonToNormalState(contact_support_modal_submit: HTMLButtonElement): void {
    contact_support_modal_submit.disabled = false;
    contact_support_modal_submit.classList.remove("sending", "thank-you");
}

function showErrorMessage(contact_support_modal_error_message: Element): void {
    contact_support_modal_error_message.classList.add("shown");
}

function hideErrorMessage(contact_support_modal_error_message: Element): void {
    contact_support_modal_error_message.classList.remove("shown");
}

function showSuccessMessage(contact_support_modal_success_message: Element): void {
    contact_support_modal_success_message.classList.add("shown");
}

function hideSuccessMessage(contact_support_modal_success_message: Element): void {
    contact_support_modal_success_message.classList.remove("shown");
}
