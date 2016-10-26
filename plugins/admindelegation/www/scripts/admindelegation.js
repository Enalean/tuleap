/**
 * Copyright (c) Enalean SAS - 2016. All rights reserved
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

    var modal_add_permission_element = document.getElementById('siteadmin-add-permission-modal');
    var modal_add_permission         = tlp.modal(modal_add_permission_element, {});

    document.getElementById('button-grant-permission').addEventListener('click', function () {
        modal_add_permission.toggle();
    });

    var modal_revoke_permission_element = document.getElementById('siteadmin-revoke-permission-modal');
    var modal_revoke_permission = tlp.modal(modal_revoke_permission_element, {});

    document.getElementById('button-revoke-permission').addEventListener('click', function () {
        modal_revoke_permission.toggle();
    });

    handlePrimaryButtonState(
        'input[type="checkbox"][name="users_to_revoke[]"]',
        '#button-revoke-permission'
    );

    function handlePrimaryButtonState(source_selector, target_button_selector) {
        var source_elements = document.querySelectorAll(source_selector),
            target_button = document.querySelector(target_button_selector);

        [].forEach.call(source_elements, function (source) {
            source.addEventListener('change', function () {
                target_button.disabled = document.querySelectorAll(source_selector + ':checked').length === 0;
            });
        });
    }
});
