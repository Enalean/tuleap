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

import { modal as createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    var dom_natures_modal_create = document.getElementById("siteadmin-config-natures-modal-create");
    var tlp_natures_modal_create = createModal(dom_natures_modal_create);
    document
        .getElementById("siteadmin-config-natures-modal-create-button")
        .addEventListener("click", function () {
            tlp_natures_modal_create.toggle();
        });

    var natures_modals_edit_buttons = document.querySelectorAll(
        ".siteadmin-config-natures-modal-edit-button"
    );
    [].forEach.call(natures_modals_edit_buttons, function (natures_modals_edit_button) {
        var dom_natures_modal_edit = document.getElementById(
            natures_modals_edit_button.getAttribute("data-edit-modal-id")
        );
        var tlp_natures_modal_edit = createModal(dom_natures_modal_edit);

        natures_modals_edit_button.addEventListener("click", function () {
            tlp_natures_modal_edit.toggle();
        });
    });

    var natures_modals_delete_buttons = document.querySelectorAll(
        ".siteadmin-config-natures-modal-delete-button"
    );
    [].forEach.call(natures_modals_delete_buttons, function (natures_modals_delete_button) {
        var dom_natures_modal_delete = document.getElementById(
            natures_modals_delete_button.getAttribute("data-delete-modal-id")
        );
        var tlp_natures_modal_delete = createModal(dom_natures_modal_delete, {});

        natures_modals_delete_button.addEventListener("click", function () {
            tlp_natures_modal_delete.toggle();
        });
    });
});
