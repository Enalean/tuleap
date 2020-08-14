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

import { dropdown, createModal, Modal } from "tlp";

export function initHelpDropdown(): void {
    const help_dropdown = document.getElementById("help");
    if (help_dropdown) {
        dropdown(help_dropdown);
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
