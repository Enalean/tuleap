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

import { get } from 'jquery';
import { modal as createModal } from 'tlp';

export default init;

function init() {
    var buttons = document.querySelectorAll(
        [
            '#add-dashboard-button',
            '#delete-dashboard-button',
            '#edit-dashboard-button',
            '#no-widgets-edit-dashboard-button',
            '.delete-widget-button',
            '.edit-widget-button'
        ].join(', ')
    );

    [].forEach.call(buttons, function (button) {
        if (! button) {
            return;
        }

        var modal_id = button.dataset.targetModalId;
        if (! modal_id) {
            throw "Missing data-target-modal-id attribute for button " + button.id;
        }

        var modal_content = document.getElementById(modal_id);
        if (! modal_content) {
            throw "Cannot find the modal " + modal_id;
        }

        var modal = createModal(modal_content);
        button.addEventListener('click', function (event) {
            event.preventDefault();
            modal.toggle();
        });

        if (button.classList.contains('edit-widget-button')) {
            modal.addEventListener('tlp-modal-shown', loadDynamicallyEditModalContent);
        }
        function loadDynamicallyEditModalContent() {
            var widget_id = modal_content.dataset.widgetId,
                container = modal_content.querySelector('.edit-widget-modal-content'),
                button    = modal_content.querySelector('button[type=submit]');

            get('/widgets/get_edit_modal_content.php?widget_id=' + encodeURIComponent(widget_id))
                .done(function (html) {
                    button.disabled     = false;
                    container.innerHTML = html;
                })
                .fail(function (data) {
                    container.innerHTML = `<div class="tlp-alert-danger">${data.responseJSON}</div>`;
                })
                .always(function () {
                    container.classList.remove('edit-widget-modal-content-loading');
                    modal.removeEventListener('tlp-modal-shown', loadDynamicallyEditModalContent);
                });
        }
    });
}
