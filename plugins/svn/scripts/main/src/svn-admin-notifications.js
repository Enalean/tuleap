/**
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

import jQuery from "jquery";
import {
    resetPlaceholder,
    addDataToAutocompleter,
    enableAutocompleter,
    loadUserAndUgroupAutocompleter,
} from "@tuleap/user-and-ugroup-autocompleter";

(function ($) {
    document.addEventListener("DOMContentLoaded", function () {
        var add_button = document.getElementById("svn-notifications-add"),
            add_row = document.getElementById("svn-notifications-row-add");

        if (!add_button || !add_row) {
            return;
        }

        var show_edit_mode_buttons = document.querySelectorAll(".svn-notification-edit-show");
        [].forEach.call(show_edit_mode_buttons, function (button) {
            button.addEventListener("click", showEditMode);
        });

        var hide_edit_mode_buttons = document.querySelectorAll(".svn-notification-edit-hide");
        [].forEach.call(hide_edit_mode_buttons, function (button) {
            button.addEventListener("click", hideEditMode);
        });

        var timeouts = [];
        var all_existing_paths = [];
        var paths = document.getElementsByClassName("input-path");
        [].forEach.call(paths, function (path) {
            if (path.classList.contains("edit-path")) {
                all_existing_paths.push({
                    notification_id: path.dataset.notificationId,
                    value: path.value,
                });
            }
            path.addEventListener("input", function (event) {
                timeouts.push(
                    setTimeout(function () {
                        clearTimeouts();
                        checkPathExists(event);
                    }, 1000)
                );
            });
        });

        initializeAutocompleter("#add_email");
        initializeAutocompleter(".edit_email");
        initializeAddNotification();

        function initializeAddNotification() {
            add_button.addEventListener("click", function () {
                hideEditMode();
                add_row.classList.remove("svn-notifications-row-add-hidden");
                add_button.classList.add("svn-notifications-add-hidden");
                resetPlaceholder("#add_email");
            });
        }

        function hideEditMode() {
            var form = document.getElementById("svn-admin-notifications-form"),
                read_cells = document.querySelectorAll(".svn-notifications-checkbox-cell-read"),
                edit_cells = document.querySelectorAll(".svn-notifications-checkbox-cell-write"),
                buttons_save = document.querySelectorAll(".svn-notification-save");

            form.reset();

            [].forEach.call(read_cells, function (cell) {
                cell.classList.remove("svn-notifications-checkbox-cell-hidden");
            });
            [].forEach.call(edit_cells, function (cell) {
                cell.classList.add("svn-notifications-checkbox-cell-hidden");
            });
            [].forEach.call(buttons_save, function (button_save) {
                button_save.disabled = false;
            });
            add_row.classList.add("svn-notifications-row-add-hidden");
            add_button.classList.remove("svn-notifications-add-hidden");
        }

        function showEditMode(event) {
            event.preventDefault();
            hideEditMode();

            var tr = this.parentNode.parentNode.parentNode,
                read_cells = tr.querySelectorAll(".svn-notifications-checkbox-cell-read"),
                edit_cells = tr.querySelectorAll(".svn-notifications-checkbox-cell-write");

            [].forEach.call(read_cells, function (cell) {
                cell.classList.add("svn-notifications-checkbox-cell-hidden");
            });
            [].forEach.call(edit_cells, function (cell) {
                cell.classList.remove("svn-notifications-checkbox-cell-hidden");
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
                selected_ugroups.concat(selected_users).concat(selected_emails)
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

        function isAnExistingPath(notification_id, path) {
            var path_found = false;
            for (var i = 0; i < all_existing_paths.length; i++) {
                if (all_existing_paths[i].notification_id === notification_id) {
                    continue;
                }

                if (all_existing_paths[i].value === path) {
                    path_found = true;
                    break;
                }
            }
            return path_found;
        }

        function clearTimeouts() {
            for (var i = 0; i < timeouts.length; i++) {
                clearTimeout(timeouts[i]);
                delete timeouts[i];
            }
            timeouts = [];
        }

        function checkPathExists(event) {
            var save_button;
            var notification_id = 0;

            if (event.target.dataset) {
                if (event.target.dataset.notificationId) {
                    notification_id = event.target.dataset.notificationId;
                }
            }
            if (notification_id !== 0) {
                save_button = $("#svn-notification-save-" + notification_id);
            } else {
                save_button = $("#svn-notification-save-adding-notification");
            }

            if (isAnExistingPath(notification_id, event.target.value)) {
                if (!save_button.attr("disabled")) {
                    save_button.popover("show");
                    save_button.attr("disabled", true);
                }
            } else {
                save_button.popover("hide");
                save_button.attr("disabled", false);
            }
        }
    });
})(jQuery);
