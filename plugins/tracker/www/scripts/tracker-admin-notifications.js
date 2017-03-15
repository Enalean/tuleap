/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

document.addEventListener('DOMContentLoaded', function () {
    var toggle_buttons = document.querySelectorAll('.tracker-notification-edit-toggle');
    [].forEach.call(toggle_buttons, function (toggle_button) {
        toggle_button.addEventListener('click', toggleEditMode);
    });

    function toggleEditMode() {
        var tr    = this.parentNode.parentNode.parentNode,
            checkboxes = tr.querySelectorAll('.tracker-global-notifications-checkbox-cell-write > input[type=checkbox]'),
            cells = tr.querySelectorAll(
                '.tracker-global-notifications-checkbox-cell-read, \
                .tracker-global-notifications-checkbox-cell-write'
            );

        resetCheckboxesToInitialState(checkboxes);

        [].forEach.call(cells, function (cell) {
            cell.classList.toggle('tracker-global-notifications-checkbox-cell-hidden');
        });
    }

    function resetCheckboxesToInitialState(checkboxes) {
        [].forEach.call(checkboxes, function (checkbox) {
            checkbox.checked = !!checkbox.dataset.checked;
        });
    }
});
