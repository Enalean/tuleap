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

import { modal as createModal }           from 'tlp';
import { autocomplete_users_for_select2 } from '../../tuleap/autocomplete-for-select2.js';

document.addEventListener('DOMContentLoaded', () => {
    initProjectMembersSelect2();
    initDeleteProjectMembersModals();
});

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

function initDeleteProjectMembersModals() {
    const modal_project_member_delete_buttons = document.querySelectorAll('.project-members-delete-button');

    for (const button of modal_project_member_delete_buttons) {
        const modal_element = document.getElementById(button.dataset.targetModalId);

        if (modal_element) {
            const modal = createModal(modal_element);

            button.addEventListener('click', function () {
                modal.toggle();
            });
        }
    }
}
