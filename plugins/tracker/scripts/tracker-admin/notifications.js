/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import {
    loadUserAndUgroupAutocompleter,
    enableAutocompleter,
    addDataToAutocompleter,
    resetPlaceholder,
} from "@tuleap/user-and-ugroup-autocompleter";

document.addEventListener("DOMContentLoaded", function () {
    var add_button_global_notif = document.getElementById("tracker-global-notifications-add"),
        add_row_global_notif = document.getElementById("tracker-global-notifications-row-add"),
        add_button_unsubscriber = document.getElementById("tracker-unsubcriber-add"),
        add_row_unsubscriber = document.getElementById("tracker-unsubscriber-row-add");

    if (
        !add_button_global_notif ||
        !add_row_global_notif ||
        !add_button_unsubscriber ||
        !add_row_unsubscriber
    ) {
        return;
    }

    var show_edit_mode_buttons = document.querySelectorAll(".tracker-notification-edit-show");
    [].forEach.call(show_edit_mode_buttons, function (button) {
        button.addEventListener("click", showEditMode);
    });

    var hide_edit_mode_buttons = document.querySelectorAll(".tracker-notification-edit-hide");
    [].forEach.call(hide_edit_mode_buttons, function (button) {
        button.addEventListener("click", hideEditMode);
    });

    initializeAutocompleter("#add_email");
    initializeAutocompleter("#add_unsubscriber");
    initializeAutocompleter(".edit_email");
    initializeAddNotification();
    initializeAddUnsubscriber();

    function initializeAddNotification() {
        add_button_global_notif.addEventListener("click", function () {
            hideEditMode();
            add_row_global_notif.classList.remove("tracker-global-notifications-row-add-hidden");
            add_button_global_notif.classList.add("tracker-global-notifications-add-hidden");
            resetPlaceholder("#add_email");
        });
    }

    function initializeAddUnsubscriber() {
        add_button_unsubscriber.addEventListener("click", function () {
            add_row_unsubscriber.classList.remove("tracker-unsubscriber-row-add-hidden");
            add_button_unsubscriber.classList.add("tracker-unsubcriber-add-hidden");
            resetPlaceholder("#add_unsubscriber");
        });
    }

    function hideEditMode() {
        var form = document.getElementById("tracker-admin-notifications-form"),
            read_cells = document.querySelectorAll(
                ".tracker-global-notifications-checkbox-cell-read",
            ),
            edit_cells = document.querySelectorAll(
                ".tracker-global-notifications-checkbox-cell-write",
            );

        form.reset();

        [].forEach.call(read_cells, function (cell) {
            cell.classList.remove("tracker-global-notifications-checkbox-cell-hidden");
        });
        [].forEach.call(edit_cells, function (cell) {
            cell.classList.add("tracker-global-notifications-checkbox-cell-hidden");
        });
        add_row_global_notif.classList.add("tracker-global-notifications-row-add-hidden");
        add_button_global_notif.classList.remove("tracker-global-notifications-add-hidden");
        add_row_unsubscriber.classList.add("tracker-unsubscriber-row-add-hidden");
        add_button_unsubscriber.classList.remove("tracker-unsubcriber-add-hidden");
    }

    function showEditMode(event) {
        event.preventDefault();
        hideEditMode();

        var tr = this.parentNode.parentNode.parentNode,
            read_cells = tr.querySelectorAll(".tracker-global-notifications-checkbox-cell-read"),
            edit_cells = tr.querySelectorAll(".tracker-global-notifications-checkbox-cell-write");

        [].forEach.call(read_cells, function (cell) {
            cell.classList.add("tracker-global-notifications-checkbox-cell-hidden");
        });
        [].forEach.call(edit_cells, function (cell) {
            cell.classList.remove("tracker-global-notifications-checkbox-cell-hidden");
            var inputs = cell.getElementsByTagName("input");
            [].forEach.call(inputs, function (input) {
                input.disabled = false;
            });
        });

        var input = document.getElementById(tr.dataset.targetInputId),
            selected_ugroups = JSON.parse(input.dataset.ugroups),
            selected_users = JSON.parse(input.dataset.users),
            selected_emails = JSON.parse(input.dataset.emails);
        addDataToAutocompleter(
            input,
            selected_ugroups.concat(selected_users).concat(selected_emails),
        );
        enableAutocompleter(input);
    }

    function initializeAutocompleter(input_id) {
        var inputs = document.querySelectorAll(input_id);

        if (!inputs) {
            return;
        }

        [].forEach.call(inputs, function (input) {
            loadUserAndUgroupAutocompleter(input);
            addDataToAutocompleter(input);
        });
    }
});
