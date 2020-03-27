/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

import { modal as createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    const details_buttons = document.querySelectorAll(".systemevents-display-edit-modal");
    for (const button of details_buttons) {
        const modal_element = document.getElementById(button.dataset.modalId),
            modal = createModal(modal_element);

        button.addEventListener("click", () => {
            modal.toggle();
        });
    }

    const types_selectors = document.querySelectorAll(".systemevents-types");
    document.getElementById("queue").addEventListener("change", function () {
        const selected_queue = this.value;
        for (const selector of types_selectors) {
            if (selector.dataset.queue === selected_queue) {
                selector.classList.add("systemevents-types-for-current-queue");
                selector.disabled = false;
            } else {
                selector.classList.remove("systemevents-types-for-current-queue");
                selector.disabled = true;
            }
        }
    });
});
