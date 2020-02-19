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

import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import { GetText } from "../../../../src/www/scripts/tuleap/gettext/gettext-init";

export const ADD_BUTTON_ID = "oauth2-server-add-client-button";
export const ADD_MODAL_ID = "oauth2-server-add-client-modal";
export const DELETE_BUTTONS_CLASS = "oauth2-server-delete-client-button";
export const DELETE_MODAL_ID = "oauth2-server-delete-client-modal";
export const DELETE_MODAL_HIDDEN_INPUT_ID = "oauth2-server-delete-client-modal-app-id";
export const DELETE_MODAL_DESCRIPTION = "oauth2-server-delete-client-modal-app-name";

export function setupAddModal(doc: Document): void {
    const button = doc.getElementById(ADD_BUTTON_ID);
    const modal_element = doc.getElementById(ADD_MODAL_ID);
    if (!button || !modal_element) {
        return;
    }

    const modal = createModal(modal_element, { keyboard: true });
    button.addEventListener("click", () => {
        modal.show();
    });
}

export function setupDeleteButtons(doc: Document, gettext_provider: GetText): void {
    const delete_buttons = doc.querySelectorAll("." + DELETE_BUTTONS_CLASS);
    for (const delete_button of delete_buttons) {
        delete_button.addEventListener("click", () => {
            if (!(delete_button instanceof HTMLElement)) {
                return;
            }
            updateDeleteModalContent(doc, delete_button, gettext_provider);
            createAndShowModal(doc);
        });
    }
}

function updateDeleteModalContent(
    doc: Document,
    button: HTMLElement,
    gettext_provider: GetText
): void {
    const modal_hidden_input = doc.getElementById(DELETE_MODAL_HIDDEN_INPUT_ID);
    const modal_description = doc.getElementById(DELETE_MODAL_DESCRIPTION);
    if (!modal_hidden_input || !(modal_hidden_input instanceof HTMLInputElement)) {
        throw new Error("Missing input hidden");
    }
    if (!modal_description) {
        throw new Error("Missing description in delete modal");
    }
    if (!button.dataset.appId) {
        throw new Error("Missing data-app-id attribute on button");
    }
    if (!button.dataset.appName) {
        throw new Error("Missing data-app-name attribute on button");
    }
    modal_hidden_input.value = button.dataset.appId;
    modal_description.innerText = "";
    modal_description.insertAdjacentText(
        "afterbegin",
        sprintf(
            gettext_provider.gettext("You are about to delete %s. Please, confirm your action."),
            button.dataset.appName
        )
    );
}

function createAndShowModal(doc: Document): void {
    const modal_element = doc.getElementById(DELETE_MODAL_ID);
    if (!modal_element) {
        throw new Error("Missing modal element");
    }

    const modal = createModal(modal_element, { destroy_on_hide: true, keyboard: true });

    modal.show();
}
