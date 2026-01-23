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

import { createModal } from "@tuleap/tlp-modal";

document.addEventListener("DOMContentLoaded", () => {
    initModalAddBot();
    initModalsWithId();

    function initModalAddBot() {
        var modal_element = document.getElementById("modal-add-bot");

        var modal_simple_content = createModal(modal_element, {});
        document.getElementById("button-modal-add-bot").addEventListener("click", function () {
            modal_simple_content.toggle();
        });
    }

    function initModalsWithId() {
        var modal_buttons = document.querySelectorAll("[data-modal-id]");
        [].forEach.call(modal_buttons, function (button) {
            var modal_element = document.getElementById(button.dataset.modalId);
            if (!modal_element) {
                throw new Error(
                    "Bad reference to an unknown modal element: '" + button.dataset.modalId + "'",
                );
            }

            var modal = createModal(modal_element);

            button.addEventListener("click", function () {
                modal.toggle();
            });
        });
    }
});
