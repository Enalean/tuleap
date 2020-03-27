/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { modal as createModal } from "tlp";

document.addEventListener("DOMContentLoaded", function () {
    const modal_element = document.getElementById("add-import-endpoint");
    const modal_simple_content = createModal(modal_element);

    document.getElementById("button-add-import-endpoint").addEventListener("click", function () {
        modal_simple_content.toggle();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const delete_modal_element = document.querySelectorAll(".delete-modal-button");
    [].forEach.call(delete_modal_element, function (button) {
        let modal_element = document.getElementById(button.dataset.modalId);

        if (modal_element) {
            let modal = createModal(modal_element);

            button.addEventListener("click", function () {
                modal.toggle();
            });
        }
    });
});
