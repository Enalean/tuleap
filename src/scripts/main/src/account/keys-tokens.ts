/**
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

import { datePicker } from "tlp";
import "@tuleap/copy-to-clipboard";
import { openTargetModalIdAfterAuthentication } from "@tuleap/webauthn";

document.addEventListener("DOMContentLoaded", () => {
    handleSSHKeys();
    handleAccessKeys();
    handleSVNTokens();
    handleCopySecretsToClipboard();
});

const ADD_SSH_KEY_BUTTON_ID = "add-ssh-key-button";
const addSSHKeyButton = (): void => {
    openTargetModalIdAfterAuthentication(document, ADD_SSH_KEY_BUTTON_ID);
};
const GENERATE_ACCESS_KEY_BUTTON_ID = "generate-access-key-button";
const addAccessKeyButton = (): void => {
    openTargetModalIdAfterAuthentication(document, GENERATE_ACCESS_KEY_BUTTON_ID);
};

function handleSSHKeys(): void {
    addSSHKeyButton();

    toggleButtonAccordingToCheckBoxesStateWithIds("remove-ssh-keys-button", "ssh_key_selected[]");

    const ssh_key = document.getElementById("ssh-key");
    if (!(ssh_key instanceof HTMLTextAreaElement)) {
        throw new Error("#ssh-key not found or is not a textarea");
    }
    const button = document.getElementById("submit-new-ssh-key-button");
    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("#submit-new-ssh-key-button not found or is not a button");
    }
    changeButtonStatusDependingTextareaStatus(button, ssh_key);

    const ssh_keys_list = document.querySelectorAll<HTMLElement>("[data-ssh_key_value]");
    ssh_keys_list.forEach((row) => {
        row.addEventListener("click", () => {
            const full_ssh_key = row.getAttribute("data-ssh_key_value");
            if (!full_ssh_key) {
                return;
            }
            row.innerText = full_ssh_key;
            row.className = "ssh-key-value-reset-cursor";
        });
    });
}

function handleAccessKeys(): void {
    addAccessKeyButton();
    addAccessKeyDatePicker();

    toggleButtonAccordingToCheckBoxesStateWithIds(
        "button-revoke-access-tokens",
        "access-keys-selected[]"
    );
    toggleButtonAccordingToCheckBoxesStateWithIds(
        "generate-new-access-key-button",
        "access-key-scopes[]"
    );
}

function addAccessKeyDatePicker(): void {
    const date_picker = document.getElementById("access-key-expiration-date-picker");
    if (!(date_picker instanceof HTMLInputElement)) {
        throw new Error(`Could not find input tag with id #access-key-expiration-date-picker`);
    }

    datePicker(date_picker);
}

function handleSVNTokens(): void {
    toggleButtonAccordingToCheckBoxesStateWithIds(
        "button-revoke-svn-tokens",
        "svn-tokens-selected[]"
    );
}

function toggleCopySecretElementVisibility(element: Element): void {
    if (element.classList.contains("user-preferences-copy-secrets-hide")) {
        element.classList.remove("user-preferences-copy-secrets-hide");
    } else {
        element.classList.add("user-preferences-copy-secrets-hide");
    }
}

function handleCopySecretsToClipboard(): void {
    document
        .querySelectorAll("copy-to-clipboard.user-preferences-copy-secret")
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

function toggleButtonAccordingToCheckBoxesStateWithIds(
    button_id: string,
    checkbox_name: string
): void {
    const button = document.getElementById(button_id);

    const checkboxes = [...document.getElementsByName(checkbox_name)].filter(
        (element): element is HTMLInputElement => element instanceof HTMLInputElement
    );

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    toggleButtonAccordingToCheckBoxesState(button, checkboxes);
}

function toggleButtonAccordingToCheckBoxesState(
    button: HTMLButtonElement,
    checkboxes: HTMLInputElement[]
): void {
    changeButtonStatusDependingCheckboxesStatus(button, checkboxes);

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener("change", () => {
            changeButtonStatusDependingCheckboxesStatus(button, checkboxes);
        });
    });
}

function changeButtonStatusDependingCheckboxesStatus(
    button: HTMLButtonElement,
    checkboxes: HTMLInputElement[]
): void {
    const at_least_one_checkbox_is_checked = checkboxes.some((checkbox) => checkbox.checked);

    if (at_least_one_checkbox_is_checked) {
        button.disabled = false;
    } else {
        button.disabled = true;
    }
}

function changeButtonStatusDependingTextareaStatus(
    button: HTMLButtonElement,
    textarea: HTMLTextAreaElement
): void {
    textarea.addEventListener("input", () => {
        const text = textarea.value;
        if (!text) {
            return;
        }

        if (text.trim() === "") {
            button.disabled = true;
        } else {
            button.disabled = false;
        }
    });
}
