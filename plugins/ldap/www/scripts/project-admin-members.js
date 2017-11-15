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
    autocomplete_groups_for_select2
} from './autocomplete-for-select2.js';

import { modal as createModal } from 'tlp';

document.addEventListener('DOMContentLoaded', () => {
    initLdapGroupsAutocompleter();
    initLdapLinkModal();
});

function initLdapGroupsAutocompleter() {
    const select = document.getElementById('project-admin-members-ldap-group-select');

    if (! select) {
        return;
    }

    autocomplete_groups_for_select2(select);
}

function initLdapLinkModal() {
    const button = document.getElementById('project-admin-members-link-ldap-button');
    if (! button) {
        return;
    }
    const modal = createModal(document.getElementById(button.dataset.targetModalId));

    button.addEventListener('click', () => {
        modal.show();
    });
}
