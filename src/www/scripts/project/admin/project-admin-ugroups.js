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

import { modal as createModal, filterInlineTable } from 'tlp';

document.addEventListener('DOMContentLoaded', () => {
    initGroupsPermissionsModal();
    initGroupsFilter();
    initUserGroupModal();
    initDeleteUserGroupModal();
});

function initGroupsPermissionsModal() {
    const button = document.getElementById('project-admin-ugroup-show-permissions-modal');
    if (! button) {
        return;
    }

    const modal = createModal(document.getElementById(button.dataset.targetModalId));

    button.addEventListener('click', () => {
        modal.show();
    });
}

function initGroupsFilter() {
    const groups_filter = document.getElementById('project-admin-ugroups-list-table-filter');
    if (groups_filter) {
        filterInlineTable(groups_filter);
    }
}

function initUserGroupModal() {
    const button = document.getElementById('project-admin-ugroups-modal');

    if (button) {
        const modal = createModal(document.getElementById(button.dataset.targetModalId));

        button.addEventListener('click', () => {
            modal.show();
        });
    }
}

function initDeleteUserGroupModal() {
    const modal_user_groups_delete_buttons = document.querySelectorAll('.project-admin-delete-ugroups-modal');
    for (const button of modal_user_groups_delete_buttons) {
        const modal_element = document.getElementById(button.dataset.targetModalId);

        if (modal_element) {
            const modal = tlp.modal(modal_element);

            button.addEventListener('click', function () {
                modal.toggle();
            });
        }
    }
}
