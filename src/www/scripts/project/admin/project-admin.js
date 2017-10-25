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

document.addEventListener('DOMContentLoaded', () => {
    initCheckboxTogglesDeleteButton();

    const select_element = document.getElementById('project-admin-details-hierarchy-project-select');
    if (! select_element) {
        return;
    }
    autocomplete(select_element, {
        include_private_projects: true
    });
});

function initCheckboxTogglesDeleteButton() {
    const checkbox_element = document.getElementById('project-admin-details-hierarchy-remove-parent-checkbox');
    const delete_button    = document.getElementById('project-admin-details-hierarchy-delete-button');
    if (! checkbox_element || ! delete_button) {
        return;
    }

    checkbox_element.addEventListener('change', (event) => {
        delete_button.disabled = ! event.target.checked;
    });
}
