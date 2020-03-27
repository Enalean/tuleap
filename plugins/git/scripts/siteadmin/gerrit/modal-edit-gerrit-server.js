/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import * as tlp from "tlp";

export function initEditModal() {
    const modal_gerrit_edit_buttons = document.querySelectorAll(".gerrit-action-edit-button");
    [].forEach.call(modal_gerrit_edit_buttons, function (button) {
        const modal_element = document.getElementById(button.dataset.modalId);

        if (modal_element) {
            const modal = tlp.modal(modal_element);

            button.addEventListener("click", function () {
                modal.toggle();
            });
        }
    });
}
