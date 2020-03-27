/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

/* global tlp:readonly */

(function () {
    "use strict";

    handleAddModal();
    handleEditModals();
    handleDeleteModals();
    handleRequiredSwitches();

    function handleAddModal() {
        var add_description_field_modal = document.getElementById("add-description-field-modal");
        var add_description_field_modal_content = tlp.modal(add_description_field_modal, {});

        document
            .getElementById("add-description-field-button")
            .addEventListener("click", function () {
                add_description_field_modal_content.toggle();
            });
    }

    function handleEditModals() {
        var edit_description_field_buttons = document.querySelectorAll(
            ".edit-description-field-button"
        );

        [].forEach.call(edit_description_field_buttons, function (edit_button) {
            var dom_edit_description_field_modal = document.getElementById(
                edit_button.dataset.modalId
            );
            var tlp_edit_description_field_modal = tlp.modal(dom_edit_description_field_modal);

            edit_button.addEventListener("click", function () {
                tlp_edit_description_field_modal.toggle();
            });
        });
    }

    function handleDeleteModals() {
        var delete_description_field_buttons = document.querySelectorAll(
            ".delete-description-field-button"
        );

        [].forEach.call(delete_description_field_buttons, function (delete_button) {
            var dom_delete_description_field_modal = document.getElementById(
                delete_button.dataset.modalId
            );
            var tlp_delete_description_field_modal = tlp.modal(dom_delete_description_field_modal);

            delete_button.addEventListener("click", function () {
                tlp_delete_description_field_modal.toggle();
            });
        });
    }

    function handleRequiredSwitches() {
        var required_switches = document.querySelectorAll(".description-field-required-switch");

        [].forEach.call(required_switches, function (required_switch) {
            required_switch.addEventListener("change", function () {
                document.getElementById(required_switch.dataset.formId).submit();
            });
        });
    }
})();
