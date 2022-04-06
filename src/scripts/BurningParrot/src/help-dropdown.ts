/**
 * Copyright (c) 2020-present, Enalean. All rights reserved
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

import type { Modal, Dropdown } from "tlp";
import { createDropdown, createModal } from "tlp";
import { post } from "@tuleap/tlp-fetch";
import { actionsOnHelpMenuOpened } from "../../user/actions-help-menu-opened";

export function initHelpDropdown(): void {
    const help_button = document.getElementById("help");
    if (help_button) {
        const help_dropdown: Dropdown = createDropdown(help_button);
        help_dropdown.addEventListener("tlp-dropdown-shown", async function (): Promise<void> {
            await actionsOnHelpMenuOpened(help_button, post);
        });
    }

    const help_shortcuts_trigger = document.getElementById("help-dropdomn-shortcuts");
    if (help_shortcuts_trigger) {
        const shortcuts_modal = document.getElementById("help-modal-shortcuts");
        const modal: Modal | null = shortcuts_modal ? createModal(shortcuts_modal) : null;
        help_shortcuts_trigger.addEventListener("click", function (event) {
            event.preventDefault();
            if (modal) {
                modal.toggle();
            }
        });
    }
}
