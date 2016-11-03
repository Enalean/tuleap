/**
 * Copyright (c) Enalean SAS, 2014 – 2016. All rights reserved
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
    var add_group = document.querySelector('#siteadmin-permission-delegation-add-group'),
        add_group_modal_element = document.querySelector('#siteadmin-permission-delegation-add-group-modal'),
        delete_group = document.querySelector('#siteadmin-permission-delegation-group-actions-delete'),
        delete_group_modal_element = document.querySelector('#siteadmin-permission-delegation-delete-group-modal'),
        edit_group = document.querySelector('#siteadmin-permission-delegation-group-actions-edit'),
        edit_group_modal_element = document.querySelector('#siteadmin-permission-delegation-edit-group-modal'),
        add_perm = document.querySelector('#siteadmin-permission-delegation-group-details-perms-actions-add'),
        add_perm_modal_element = document.querySelector('#siteadmin-permission-delegation-add-perm-modal'),
        modal_add_group = tlp.modal(add_group_modal_element, {keyboard: true}),
        add_user = document.getElementById('siteadmin-permission-delegation-group-details-users-actions-add-input');

    add_group.addEventListener('click', function () {
        modal_add_group.toggle();
        initFocus(add_group_modal_element);
    });

    if (delete_group && delete_group_modal_element) {
        var modal_delete_group = tlp.modal(delete_group_modal_element, {keyboard: true});

        delete_group.addEventListener('click', function () {
            modal_delete_group.toggle();
            initFocus(delete_group_modal_element);
        });
    }

    if (edit_group && edit_group_modal_element) {
        var modal_edit_group = tlp.modal(edit_group_modal_element, {keyboard: true});

        edit_group.addEventListener('click', function () {
            modal_edit_group.toggle();
            initFocus(edit_group_modal_element);
        });
    }

    if (add_perm && add_perm_modal_element) {
        var modal_add_perm = tlp.modal(add_perm_modal_element, {keyboard: true});

        add_perm.addEventListener('click', function () {
            modal_add_perm.toggle();
            initFocus(add_perm_modal_element);
        });
    }

    if (add_user) {
        tuleap.autocomplete_users_for_select2(add_user, { internal_users_only: 1 });
    }

    handlePrimaryButtonState(
        '#siteadmin-permission-delegation-group-details-perms input[type="checkbox"][name="permissions[]"]',
        '#siteadmin-permission-delegation-group-details-perms-actions-delete'
    );

    handlePrimaryButtonState(
        '#siteadmin-permission-delegation-add-perm-modal input[type="checkbox"][name="permissions[]"]',
        '#siteadmin-permission-delegation-add-perm-modal-submit'
    );

    handlePrimaryButtonState(
        'input[type="checkbox"][name="user-ids[]"]',
        '#siteadmin-permission-delegation-group-details-users-actions-delete'
    );

    function handlePrimaryButtonState(source_selector, target_button_selector) {
        var source_elements = document.querySelectorAll(source_selector),
            target_button   = document.querySelector(target_button_selector);

        [].forEach.call(source_elements, function(source) {
            source.addEventListener('change', function () {
                target_button.disabled = document.querySelectorAll(source_selector + ':checked').length === 0;
            });
        });
    }

    function initFocus(modal) {
        var first_element = modal.querySelector('input:first-child');

        if (! first_element) {
            first_element = modal.querySelector('input[type="submit"]');
        }

        if (! first_element) {
            first_element = modal.querySelector('button[type="submit"]');
        }

        if (first_element) {
            first_element.focus();
        }
    }
});