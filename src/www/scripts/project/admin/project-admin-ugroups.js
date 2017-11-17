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
    initModals();
    initGroupsFilter();
    initBindingDependencies();
});

function initModals() {
    const buttons = document.querySelectorAll(`
        #project-admin-ugroup-add-binding,
        #project-admin-ugroup-show-permissions-modal,
        #project-admin-ugroups-modal,
        #project-admin-delete-binding,
        .project-admin-delete-ugroups-modal,
        .project-admin-remove-user-from-group
    `);

    for (const button of buttons) {
        const modal = createModal(document.getElementById(button.dataset.targetModalId));

        button.addEventListener('click', () => {
            modal.show();
        });
    }
}

function initGroupsFilter() {
    const groups_filter = document.getElementById('project-admin-ugroups-list-table-filter');
    if (groups_filter) {
        filterInlineTable(groups_filter);
    }
}

function initBindingDependencies() {
    const project_selectbox = document.getElementById('project-admin-ugroup-add-binding-project');
    const ugroup_selectbox  = document.getElementById('project-admin-ugroup-add-binding-ugroup');

    if (! project_selectbox || ! ugroup_selectbox) {
        return;
    }

    project_selectbox.addEventListener('change', mapUgroupsSelectboxToProjectSelectbox);
    mapUgroupsSelectboxToProjectSelectbox();

    function mapUgroupsSelectboxToProjectSelectbox() {
        let i = ugroup_selectbox.options.length;
        while (--i > 0) {
            ugroup_selectbox.remove(i);
        }

        const selected_option = project_selectbox.options[project_selectbox.selectedIndex];
        if (! selected_option.value) {
            return;
        }

        for (const ugroup of JSON.parse(selected_option.dataset.ugroups)) {
            ugroup_selectbox.options.add(new Option(ugroup['name'], ugroup['id']));
        }
    }
}
