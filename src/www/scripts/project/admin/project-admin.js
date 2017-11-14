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

import { autocomplete_projects_for_select2 as autocomplete } from '../../tuleap/autocomplete-for-select2.js';
import { modal as createModal, filterInlineTable } from 'tlp';

document.addEventListener('DOMContentLoaded', () => {
    initTOSCheckbox();
    initHierarchyModal();
    initGroupsPermissionsModal();
    initGroupsFilter();

    const select_element = document.getElementById('project-admin-details-hierarchy-project-select');
    if (! select_element) {
        return;
    }
    autocomplete(select_element, {
        include_private_projects: true
    });
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

function initHierarchyModal() {
    const button = document.getElementById('project-admin-details-hierarchy-delete-button');
    if (! button) {
        return;
    }

    const modal = createModal(document.getElementById(button.dataset.targetModalId));

    button.addEventListener('click', () => {
        modal.show();
    });
}

function initTOSCheckbox() {
    const select_element = document.getElementById('project_visibility');
    if (! select_element) {
        return;
    }
    select_element.addEventListener('change', () => {
        document.getElementById("term-of-service").required = true;
        document.getElementById("term-of-service-usage").style.display = 'block';
    });
}
