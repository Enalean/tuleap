/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { createPopover } from "@tuleap/tlp-popovers";
import { openAllTargetModalsOnClick } from "@tuleap/core/scripts/tuleap/modals/modal-opener";
import { bindTypeSelectorToMultipleValuesCheckbox } from "./admin/bind-type-selector-to-multiple-values-checkbox";

document.addEventListener("DOMContentLoaded", () => {
    registerPopoverOnDeleteButtons();
    openAllTargetModalsOnClick(document, ".docman-admin-properties-modal-button");
    bindTypeSelectorToMultipleValuesCheckbox(document);
});

function registerPopoverOnDeleteButtons(): void {
    const delete_buttons = document.querySelectorAll(".docman-admin-properties-delete-button");
    for (const button of delete_buttons) {
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Button is not a button");
        }

        const target_id = button.dataset.target;
        if (!target_id) {
            throw Error("Button does not have target");
        }

        const target = document.getElementById(target_id);
        if (!target) {
            throw Error("Target does not exist");
        }

        createPopover(button, target, { trigger: "click", placement: "left" });
    }
}
