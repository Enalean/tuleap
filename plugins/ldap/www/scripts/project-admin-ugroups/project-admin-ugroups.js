/*
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

import Gettext from 'node-gettext';
import french_translations from './po/fr.po';
import { autocomplete_groups_for_select2 } from '../autocomplete-for-select2.js';
import { render } from 'mustache';
import preview_template from './preview.mustache';
import { sprintf } from 'sprintf-js';
import { get, modal as createModal } from 'tlp';

document.addEventListener('DOMContentLoaded', () => {
    initModal();
    initLdapGroupsAutocompleter();
});

function initModal() {
    const button = document.getElementById('project-admin-ugroup-add-ldap-binding');

    if (! button) {
        return;
    }

    const modal = createModal(document.getElementById(button.dataset.targetModalId));

    button.addEventListener('click', () => {
        modal.show();
    });
}

function initLdapGroupsAutocompleter() {
    const select   = document.getElementById('project-admin-ugroup-binding-ldap-group');
    if (! select) {
        return;
    }
    const preserve    = document.getElementById('project-admin-ugroup-binding-ldap-group-preserve');
    const button      = document.getElementById('project-admin-ugroup-ldap-binding-modal-link-button');
    const preview     = document.getElementById('project-admin-ugroup-ldap-binding-modal-preview');
    const ugroup_id   = select.dataset.ugroupId;
    const ugroup_name = select.dataset.ugroupName;

    const gettext_provider = new Gettext();
    gettext_provider.addTranslations('fr_FR', 'ldap-ugroups', french_translations);
    gettext_provider.setLocale(select.dataset.locale);
    gettext_provider.setTextDomain('ldap-ugroups');

    const select2 = autocomplete_groups_for_select2(select, {
        allowClear: true
    });
    select2.on('change', mapPreviewToChosenLdapGroup);
    preserve.addEventListener('click', mapPreviewToChosenLdapGroup);
    mapPreviewToChosenLdapGroup();

    async function mapPreviewToChosenLdapGroup() {
        button.disabled = true;

        const chosen_ldap_group = select.value;
        if (! chosen_ldap_group) {
            preview.classList.remove('shown');
            return;
        }

        removeAllChildren(preview);
        preview.classList.add('shown');
        preview.classList.add('loading');

        const { users_to_add, users_to_remove, nb_not_impacted } = await getUsersToConfirm(chosen_ldap_group, ugroup_id);
        preview.classList.remove('loading');

        const rendered_preview = render(
            preview_template,
            getPresenter(users_to_add, users_to_remove, ugroup_name, chosen_ldap_group, nb_not_impacted)
        );
        preview.insertAdjacentHTML('beforeEnd', rendered_preview);

        button.disabled = false;
    }

    async function getUsersToConfirm(chosen_ldap_group, ugroup_id) {
        const url = '/plugins/ldap/bind_ugroup_confirm.php'
            + '?bind_with_group=' + encodeURIComponent(chosen_ldap_group)
            + '&ugroup_id=' + encodeURIComponent(ugroup_id)
            + '&preserve_members=' + (preserve.checked ? 1 : 0);

        const response = await get(url);

        return await response.json();
    }

    function removeAllChildren(element) {
        [...element.children].forEach(child => child.remove());
    }

    function getPresenter(users_to_add, users_to_remove, ugroup_name, chosen_ldap_group, nb_not_impacted) {
        const nb_to_add    = users_to_add.length;
        const nb_to_remove = users_to_remove.length;

        const title             = sprintf(gettext_provider.gettext('Binding %s to directory group %s'), ugroup_name, chosen_ldap_group);
        const nb_to_add_text    = sprintf(gettext_provider.ngettext('%d user to add.', '%d users to add.', nb_to_add), nb_to_add);
        const nb_to_remove_text = sprintf(gettext_provider.ngettext('%d user to remove.', '%d users to remove.', nb_to_remove), nb_to_remove);

        const nothing_to_do_text   = gettext_provider.gettext("There aren't any users to add nor to remove.");
        const nb_not_impacted_text = sprintf(gettext_provider.ngettext('%d user not impacted.', '%d users not impacted.', nb_not_impacted), nb_not_impacted);

        return {
            title,
            nb_to_add,
            users_to_add,
            nb_to_remove,
            nb_to_add_text,
            users_to_remove,
            nb_not_impacted,
            nb_to_remove_text,
            nothing_to_do_text,
            nb_not_impacted_text
        };
    }
}
