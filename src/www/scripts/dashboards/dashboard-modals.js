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
    var buttons = [
        document.getElementById('add-dashboard-button'),
        document.getElementById('delete-dashboard-button'),
        document.getElementById('edit-dashboard-button'),
        document.getElementById('no-widgets-edit-dashboard-button')
    ];

    buttons.forEach(function (button) {
        if (! button) {
            return;
        }

        var modal_id = button.dataset.targetModalId;
        if (! modal_id) {
            throw "Missing data-target-modal-id attribute for button "+ button.id;
        }

        var modal_content = document.getElementById(modal_id);
        if (! modal_content) {
            throw "Cannot find the modal "+ modal_id;
        }

        var modal = tlp.modal(modal_content);
        button.addEventListener('click', function (event) {
            event.preventDefault();
            modal.toggle();
        });
    });
});
