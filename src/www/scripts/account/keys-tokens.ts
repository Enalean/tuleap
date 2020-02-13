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

import { modal as createModal, datePicker } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    addAccessKeyButton();
    addAccessKeyDatePicker();
    toggleButtonsAccordingToCheckBoxesStates();
});

function addAccessKeyButton(): void {
    const button = document.getElementById("generate-access-key-button");

    if (button && button.dataset) {
        const modal_target_id = button.dataset.targetModalId;

        if (!modal_target_id) {
            return;
        }

        const modal_element = document.getElementById(modal_target_id);
        if (!modal_element) {
            return;
        }
        const modal = createModal(modal_element);

        button.addEventListener("click", () => {
            modal.show();
        });
    }
}

function addAccessKeyDatePicker(): void {
    const date_picker = document.getElementById("access-key-expiration-date-picker");

    if (!date_picker) {
        return;
    }

    datePicker(date_picker);
}

function toggleButtonsAccordingToCheckBoxesStates(): void {
    toggleButtonAccordingToCheckBoxesStateWithIds(
        "button-revoke-access-tokens",
        "access-keys-selected[]"
    );
    toggleButtonAccordingToCheckBoxesStateWithIds(
        "generate-new-access-key-button",
        "access-key-scopes[]"
    );
}

function toggleButtonAccordingToCheckBoxesStateWithIds(
    button_id: string,
    checkbox_name: string
): void {
    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    const button = document.getElementById(button_id) as HTMLButtonElement;

    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    const checkboxes = document.getElementsByName(checkbox_name) as NodeListOf<HTMLInputElement>;

    if (!button) {
        return;
    }

    toggleButtonAccordingToCheckBoxesState(button, checkboxes);
}

function toggleButtonAccordingToCheckBoxesState(
    button: HTMLButtonElement,
    checkboxes: NodeListOf<HTMLInputElement>
): void {
    changeButtonStatusDependingCheckboxesStatus(button, checkboxes);

    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", () => {
            changeButtonStatusDependingCheckboxesStatus(button, checkboxes);
        });
    });
}

function changeButtonStatusDependingCheckboxesStatus(
    button: HTMLButtonElement,
    checkboxes: NodeListOf<HTMLInputElement>
): void {
    let at_least_one_checkbox_is_checked = false;

    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            at_least_one_checkbox_is_checked = true;
            return;
        }
    });

    if (at_least_one_checkbox_is_checked) {
        button.disabled = false;
    } else {
        button.disabled = true;
    }
}
