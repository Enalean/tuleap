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

import { browserSupportsWebAuthn } from "@simplewebauthn/browser";
import { EVENT_TLP_MODAL_HIDDEN, openTargetModalIdOnClick } from "@tuleap/tlp-modal";
import type { GetText } from "@tuleap/gettext";
import { getPOFileFromLocaleWithoutExtension, initGettext } from "@tuleap/gettext";
import { selectOrThrow } from "@tuleap/dom";
import "../themes/style.scss";
import { register } from "./register";
import { authenticate } from "./authenticate";
import { deleteKey } from "./delete";

const HIDDEN = "webauthn-hidden";

document.addEventListener("DOMContentLoaded", (): void => {
    prepareGettext().then((gettext_provider) => {
        if (browserSupportsWebAuthn()) {
            const webauthn_section = document.querySelector("#webauthn-section");
            if (webauthn_section instanceof HTMLElement) {
                webauthn_section.classList.remove(HIDDEN);
            }

            prepareRegistration();
            prepareAuthentication(gettext_provider);
            prepareRemove();
        } else {
            const disabled_section = document.querySelector("#webauthn-disabled-section");
            if (disabled_section instanceof HTMLElement) {
                disabled_section.classList.remove(HIDDEN);
            }
        }
    });
});

function prepareGettext(): Promise<GetText> {
    const language = document.body.dataset.userLocale ?? "en_US";

    return initGettext(
        language,
        "webauthn",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`)
    );
}

function prepareRegistration(): void {
    const form_name_modal = selectOrThrow(document, "#webauthn-name-modal");
    const name_modal_input = selectOrThrow(
        form_name_modal,
        "#webauthn-name-input",
        HTMLInputElement
    );
    const csrf_modal_input = selectOrThrow(
        form_name_modal,
        "input[name=challenge]",
        HTMLInputElement
    );
    const error = selectOrThrow(document, "#webauthn-add-error");
    const add_button_icon = selectOrThrow(document, "#webauthn-modal-add");

    function registration(name: string, csrf_token: string): void {
        register(name, csrf_token).match(
            () => {
                location.reload();
            },
            (fault) => {
                add_button_icon.classList.add(HIDDEN);
                error.classList.remove(HIDDEN);
                error.innerText = fault.toString();
            }
        );
    }

    const name_modal = openTargetModalIdOnClick(document, "webauthn-add-button");
    if (name_modal === null) {
        return;
    }
    form_name_modal.addEventListener("submit", (event) => {
        event.preventDefault();

        const name = name_modal_input.value.trim();
        const csrf_token = csrf_modal_input.value;

        error.classList.add(HIDDEN);
        add_button_icon.classList.remove(HIDDEN);
        registration(name, csrf_token);
    });

    name_modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
        name_modal_input.value = "";
    });
}

function prepareAuthentication(gettext_provider: GetText): void {
    const check_button = document.querySelector("#webauthn-check-button");
    const button_icon = document.querySelector("#webauthn-check-button > i");
    const message = document.querySelector("#webauthn-alert");
    if (
        !(check_button instanceof HTMLButtonElement) ||
        !(button_icon instanceof HTMLElement) ||
        !(message instanceof HTMLElement)
    ) {
        return;
    }

    check_button.addEventListener("click", () => {
        button_icon.classList.remove(HIDDEN);

        authenticate().match(
            () => {
                button_icon.classList.add(HIDDEN);
                message.innerText = gettext_provider.gettext("Success!");
                message.classList.remove("tlp-alert-danger");
                message.classList.add("tlp-alert-success");
                message.classList.remove(HIDDEN);
                setTimeout(() => message.classList.add(HIDDEN), 5000);
            },
            (fault) => {
                button_icon.classList.add(HIDDEN);
                message.innerText = fault.toString();
                message.classList.remove("tlp-alert-success");
                message.classList.add("tlp-alert-danger");
                message.classList.remove(HIDDEN);
            }
        );
    });
}

function prepareRemove(): void {
    const form_remove_modal = selectOrThrow(document, "#webauthn-remove-modal");
    const key_id_input = selectOrThrow(document, "#webauthn-key-id-input", HTMLInputElement);
    const error = selectOrThrow(form_remove_modal, "#webauthn-remove-error");
    const remove_button = selectOrThrow(
        form_remove_modal,
        "#webauthn-modal-remove-button",
        HTMLButtonElement
    );
    const remove_button_icon = selectOrThrow(form_remove_modal, "#webauthn-modal-remove");

    document.querySelectorAll("[data-item-id=webauthn-remove]").forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const modal = openTargetModalIdOnClick(document, button.id);
        if (modal === null) {
            return;
        }
        modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
            error.classList.add(HIDDEN);
        });

        button.addEventListener("click", () => {
            key_id_input.value = button.id;
        });
    });

    form_remove_modal.addEventListener("submit", (event) => {
        event.preventDefault();

        const key_id = key_id_input.value;

        remove_button_icon.classList.remove(HIDDEN);
        remove_button.disabled = true;

        deleteKey(key_id).match(
            () => location.reload(),
            (fault) => {
                remove_button_icon.classList.add(HIDDEN);
                remove_button.disabled = false;
                error.innerText = fault.toString();
                error.classList.remove(HIDDEN);
            }
        );
    });
}
