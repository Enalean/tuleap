/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { PopupPickerController } from "@picmo/popup-picker";

const ICON_PICKER_EVENT = "emoji:select";

export function initIconPicker(doc: Document, icon_picker: PopupPickerController | null): void {
    if (!icon_picker) {
        return;
    }

    const icon_input = doc.getElementById("icon-input");
    const icon_removal_button = doc.getElementById("icon-removal-button");

    if (
        !(icon_removal_button instanceof HTMLButtonElement) ||
        !(icon_input instanceof HTMLInputElement)
    ) {
        return;
    }

    if (icon_input.value.length > 0) {
        showIconRemovalButton(icon_removal_button);
    }

    icon_picker.addEventListener(ICON_PICKER_EVENT, (selection) => {
        icon_input.setAttribute("value", selection.emoji);
        showIconRemovalButton(icon_removal_button);
    });

    icon_input.addEventListener("click", () => {
        icon_picker.toggle();
    });

    icon_removal_button.addEventListener("click", () => {
        icon_input.removeAttribute("value");
        hideIconRemovalButton(icon_removal_button);
    });
}

function showIconRemovalButton(icon_removal_button: HTMLButtonElement): void {
    icon_removal_button.classList.add("show");
    if (icon_removal_button.parentElement === null) {
        return;
    }

    icon_removal_button.parentElement.classList.add("tlp-form-element-append");
}

function hideIconRemovalButton(icon_removal_button: HTMLButtonElement): void {
    icon_removal_button.classList.remove("show");
    if (icon_removal_button.parentElement === null) {
        return;
    }

    icon_removal_button.parentElement.classList.remove("tlp-form-element-append");
}
