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

import {filterInlineTable, get, modal as createModal} from 'tlp';
import {sanitize} from 'dompurify';
import Gettext from 'node-gettext';
import french_translations from '../po/fr.po';

const gettext_provider = new Gettext();

document.addEventListener('DOMContentLoaded', () => {


    const member_list_container = document.getElementById('project-admin-user-groups-member-list-container');
    if (member_list_container) {
        gettext_provider.addTranslations('fr_FR', 'project-admin', french_translations);
        gettext_provider.setLocale(member_list_container.dataset.locale);
        gettext_provider.setTextDomain('project-admin');
    }

    initModals();
    initModalAddDynamicUserToUGroup();
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

async function initModalAddDynamicUserToUGroup() {
    const button = document.getElementById('project-admin-add-dynamic-modal');
    if (! button) {
        return;
    }

    button.addEventListener('click', () => {
        const selected_user = document.getElementById('project-admin-members-add-user-select').value;
        if (! selected_user) {
            return;
        }

        document.getElementById('project-administration-add-dynamic-ugroup-icon').classList.remove('fa-plus');
        document.getElementById('project-administration-add-dynamic-ugroup-icon').classList.add('fa-spin');
        document.getElementById('project-administration-add-dynamic-ugroup-icon').classList.add('fa-spinner');

        document.getElementById('add-user-to-ugroup').value = selected_user;
        initModalOrSendForm(selected_user);
    });
}

function openConfirmationModal(selected_user) {
    const button               = document.getElementById('project-admin-add-dynamic-modal');
    const modal                = createModal(document.getElementById(button.dataset.targetModalId));
    const ugroup_name          = document.getElementById('user-group').value;
    const confirmation_message = sprintf(
        gettext_provider.gettext('You are about to add <b>%s</b> in <b>%s</b> users group.'),
        selected_user,
        ugroup_name
    );

    document.getElementById('add-user-to-dynamic-ugroup-confirmation-message').innerHTML = sanitize(confirmation_message);

    modal.show();
    document.getElementById('project-administration-add-dynamic-ugroup-icon').classList.add('fa-plus');
    document.getElementById('project-administration-add-dynamic-ugroup-icon').classList.remove('fa-spin');
    document.getElementById('project-administration-add-dynamic-ugroup-icon').classList.remove('fa-spinner');
}

async function initModalOrSendForm(identifier) {
    const button     = document.getElementById('project-admin-add-dynamic-modal');
    const project_id = button.dataset.projectId;
    const ugroup_id  = button.dataset.ugroupId;

    const ugroup_identifier = project_id + '_' + ugroup_id;

    const response = await get('/api/v1/user_groups/' + ugroup_identifier + '/users', {
        params: {
            query: JSON.stringify({identifier})
        }
    });
    const users = await response.json();

    if (users.length === 0) {
        openConfirmationModal(identifier);
    } else {
        document.getElementById("add-user-to-dynamic-ugroup").submit();
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
