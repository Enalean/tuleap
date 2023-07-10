/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { GettextProvider } from "@tuleap/gettext";

/**
 * A custom modal to authenticate with WebAuthn.
 */
export class AuthenticationModal extends HTMLElement {
    // Modals
    private current_modal: Modal | null;
    target_modal: Modal | null;

    // DOM properties
    private modal_form: HTMLFormElement | null;

    private gettext_provider: GettextProvider | null;

    constructor() {
        super();
        this.current_modal = null;
        this.target_modal = null;
        this.modal_form = null;
        this.gettext_provider = null;
    }

    connectedCallback(): void {
        this.buildModal();

        this.modal_form?.addEventListener("submit", (event) => this.submitForm(event));
    }

    submitForm(event: Event): void {
        event.preventDefault();
        this.hide();
        this.target_modal?.show();
    }

    buildModal(): void {
        if (this.gettext_provider === null) {
            throw new Error("GettextProvider not set");
        }

        const modal = document.createElement("div");
        modal.classList.add("tlp-modal");
        modal.role = "dialog";
        modal.setAttribute("aria-labelledby", "webauthn-modal-title");

        // Header
        const modal_header = document.createElement("div");
        modal_header.classList.add("tlp-modal-header");
        const modal_title = document.createElement("h1");
        modal_title.classList.add("tlp-modal-title");
        modal_title.id = "webauthn-modal-title";
        modal_title.innerText = this.gettext_provider.gettext("Passkey authentication");
        modal_header.appendChild(modal_title);
        const modal_close = document.createElement("button");
        modal_close.classList.add("tlp-modal-close");
        modal_close.type = "button";
        modal_close.setAttribute("data-dismiss", "modal");
        modal_close.ariaLabel = this.gettext_provider.gettext("Close");
        const modal_close_icon = document.createElement("i");
        modal_close_icon.classList.add("fa-solid", "fa-times", "tlp-modal-close-icon");
        modal_close_icon.ariaHidden = "true";
        modal_close.appendChild(modal_close_icon);
        modal_header.appendChild(modal_close);

        modal.appendChild(modal_header);

        // Body + footer
        this.modal_form = document.createElement("form");
        const modal_body = document.createElement("div");
        modal_body.classList.add("tlp-modal-body");
        const modal_body_text = document.createElement("div");
        modal_body_text.innerText = this.gettext_provider.gettext(
            "Please prepare your passkey to confirm your identity"
        );
        modal_body.appendChild(modal_body_text);
        this.modal_form.appendChild(modal_body);
        const modal_footer = document.createElement("div");
        modal_footer.classList.add("tlp-modal-footer");
        const modal_footer_cancel = document.createElement("button");
        modal_footer_cancel.type = "button";
        modal_footer_cancel.classList.add(
            "tlp-button-primary",
            "tlp-button-outline",
            "tlp-modal-action"
        );
        modal_footer_cancel.setAttribute("data-dismiss", "modal");
        modal_footer_cancel.innerText = this.gettext_provider.gettext("Cancel");
        modal_footer.appendChild(modal_footer_cancel);
        const modal_footer_submit = document.createElement("button");
        modal_footer_submit.type = "submit";
        modal_footer_submit.classList.add("tlp-button-primary", "tlp-modal-action");
        modal_footer_submit.setAttribute("data-test", "webauthn-modal-submit-button");
        modal_footer_submit.append(this.gettext_provider.gettext("OK"));
        modal_footer.appendChild(modal_footer_submit);
        this.modal_form.appendChild(modal_footer);

        modal.appendChild(this.modal_form);

        this.current_modal = createModal(modal);
        this.appendChild(modal);
    }

    setTargetModal(target_modal: Modal): void {
        this.target_modal = target_modal;
    }

    setGettextProvider(gettext_provider: GettextProvider): void {
        this.gettext_provider = gettext_provider;
    }

    show(): void {
        this.current_modal?.show();
    }

    hide(): void {
        this.current_modal?.hide();
    }
}

export const AUTHENTICATION_MODAL_TAG = "tuleap-webauthn-authentication-modal";
if (!customElements.get(AUTHENTICATION_MODAL_TAG)) {
    customElements.define(AUTHENTICATION_MODAL_TAG, AuthenticationModal);
}
