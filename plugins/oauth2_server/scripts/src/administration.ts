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

import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import {
    openModalAndReplacePlaceholders,
    openTargetModalIdOnClick,
    openAllTargetModalsOnClick,
} from "@tuleap/tlp-modal";
import {
    buildDeletionReplaceCallback,
    buildRegenerationReplaceBallback,
    hiddenInputReplaceCallback,
} from "./replacers";
import "@tuleap/copy-to-clipboard";
import "../../themes/administration.scss";

const ADD_BUTTON_ID = "oauth2-server-add-client-button";

const NEW_SECRET_BUTTONS_SELECTOR = "[data-new-client-secret-button]";
const NEW_SECRET_MODAL_ID = "oauth2-server-new-secret-modal";
const NEW_SECRET_MODAL_APP_ID = "oauth2-server-new-secret-app-id";
const NEW_SECRET_MODAL_DESCRIPTION = "oauth2-server-new-secret-app-name";

const EDIT_BUTTONS_SELECTOR = "[data-edit-client-button]";

const DELETE_BUTTONS_SELECTOR = "[data-delete-client-button]";
const DELETE_MODAL_ID = "oauth2-server-delete-client-modal";
const DELETE_MODAL_HIDDEN_INPUT_ID = "oauth2-server-delete-client-modal-app-id";
const DELETE_MODAL_DESCRIPTION = "oauth2-server-delete-client-modal-app-name";

document.addEventListener("DOMContentLoaded", async () => {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }
    const gettext_provider = await initGettext(
        language,
        "tuleap-oauth2_server",
        (locale) =>
            import(
                /* webpackChunkName: "oauth2-server-po-" */ "../po/" + getPOFileFromLocale(locale)
            ),
    );

    openTargetModalIdOnClick(document, ADD_BUTTON_ID);
    openAllTargetModalsOnClick(document, EDIT_BUTTONS_SELECTOR);
    openModalAndReplacePlaceholders({
        document: document,
        buttons_selector: DELETE_BUTTONS_SELECTOR,
        modal_element_id: DELETE_MODAL_ID,
        hidden_input_replacement: {
            input_id: DELETE_MODAL_HIDDEN_INPUT_ID,
            hiddenInputReplaceCallback,
        },
        paragraph_replacement: {
            paragraph_id: DELETE_MODAL_DESCRIPTION,
            paragraphReplaceCallback: buildDeletionReplaceCallback(gettext_provider),
        },
    });
    openModalAndReplacePlaceholders({
        document: document,
        buttons_selector: NEW_SECRET_BUTTONS_SELECTOR,
        modal_element_id: NEW_SECRET_MODAL_ID,
        hidden_input_replacement: {
            input_id: NEW_SECRET_MODAL_APP_ID,
            hiddenInputReplaceCallback,
        },
        paragraph_replacement: {
            paragraph_id: NEW_SECRET_MODAL_DESCRIPTION,
            paragraphReplaceCallback: buildRegenerationReplaceBallback(gettext_provider),
        },
    });
    handleCopyClientSecretToClipboard();
});

function toggleCopySecretElementVisibility(element: Element): void {
    if (element.classList.contains("oauth2-server-copy-secret-hide")) {
        element.classList.remove("oauth2-server-copy-secret-hide");
    } else {
        element.classList.add("oauth2-server-copy-secret-hide");
    }
}

function handleCopyClientSecretToClipboard(): void {
    document
        .querySelectorAll("copy-to-clipboard.oauth2-server-copy-secret")
        .forEach((element: Element) => {
            let already_copied = false;
            element.addEventListener("copied-to-clipboard", () => {
                if (already_copied) {
                    return;
                }
                already_copied = true;
                const children = [...element.children];
                children.forEach(toggleCopySecretElementVisibility);
                setTimeout(() => {
                    children.forEach(toggleCopySecretElementVisibility);
                    already_copied = false;
                }, 2000);
            });
        });
}
