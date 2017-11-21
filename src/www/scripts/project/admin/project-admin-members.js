/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
    modal as createModal,
    filterInlineTable
} from 'tlp';

import { autocomplete_users_for_select2 } from '../../tuleap/autocomplete-for-select2.js';

document.addEventListener('DOMContentLoaded', () => {
    initProjectMembersSelect2();
    initMembersFilter();
    initModals();
});

function initModals() {
    const buttons = document.querySelectorAll(`
        #project-admin-members-modal-import-users-button,
        .project-members-delete-button
    `);

    for (const button of buttons) {
        const modal = createModal(document.getElementById(button.dataset.targetModalId));

        button.addEventListener('click', () => {
            modal.show();
        });
    }
}

function initProjectMembersSelect2()
{
    const select_element = document.getElementById('project-admin-members-add-user-select');

    if (! select_element) {
        return;
    }

    autocomplete_users_for_select2(select_element, {
        internal_users_only: false
    });
}

function initMembersFilter() {
    const members_filter = document.getElementById('project-admin-members-list-filter-table');

    if (members_filter) {
        filterInlineTable(members_filter);
    }
}
