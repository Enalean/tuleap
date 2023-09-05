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

import { createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    var dom_types_modal_create = document.getElementById("siteadmin-config-types-modal-create");
    if (!dom_types_modal_create) {
        throw new Error("siteadmin-config-types-modal-create DOM element not found");
    }

    var tlp_types_modal_create = createModal(dom_types_modal_create);
    document
        .getElementById("siteadmin-config-types-modal-create-button")
        .addEventListener("click", function () {
            tlp_types_modal_create.toggle();
        });

    var types_modals_edit_buttons = document.querySelectorAll(
        ".siteadmin-config-types-modal-edit-button",
    );
    [].forEach.call(types_modals_edit_buttons, function (types_modals_edit_button) {
        var dom_types_modal_edit = document.getElementById(
            types_modals_edit_button.getAttribute("data-edit-modal-id"),
        );
        var tlp_types_modal_edit = createModal(dom_types_modal_edit);

        types_modals_edit_button.addEventListener("click", function () {
            tlp_types_modal_edit.toggle();
        });
    });

    var types_modals_delete_buttons = document.querySelectorAll(
        ".siteadmin-config-types-modal-delete-button",
    );
    [].forEach.call(types_modals_delete_buttons, function (types_modals_delete_button) {
        var dom_types_modal_delete = document.getElementById(
            types_modals_delete_button.getAttribute("data-delete-modal-id"),
        );
        var tlp_types_modal_delete = createModal(dom_types_modal_delete, {});

        types_modals_delete_button.addEventListener("click", function () {
            tlp_types_modal_delete.toggle();
        });
    });
});
