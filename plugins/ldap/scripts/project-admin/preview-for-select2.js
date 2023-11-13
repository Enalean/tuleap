/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import mustache from "mustache";
import { sprintf } from "sprintf-js";
import preview_template from "./preview.mustache";
import { autocomplete_groups_for_select2 } from "../autocomplete-for-select2.js";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";

export async function initLdapBindingPreview(options, callback) {
    const { preserve, button, preview, display_name, select } = options;

    const gettext_provider = await initGettext(
        select.dataset.locale,
        "ldap-bindings",
        (locale) =>
            import(
                /* webpackChunkName: "project-admin-ldap-po-" */ "./po/" +
                    getPOFileFromLocale(locale)
            ),
    );

    const select2 = autocomplete_groups_for_select2(select, {
        allowClear: true,
    });
    select2.on("change", mapPreviewToChosenLdapGroup);
    preserve.addEventListener("click", mapPreviewToChosenLdapGroup);
    mapPreviewToChosenLdapGroup();

    async function mapPreviewToChosenLdapGroup() {
        button.disabled = true;

        const chosen_ldap_group = select.value;

        if (!chosen_ldap_group) {
            preview.classList.remove("shown");
            return;
        }

        removeAllChildren(preview);
        preview.classList.add("shown");
        preview.classList.add("loading");

        let users_to_add, users_to_remove, nb_not_impacted, group_not_found;
        try {
            ({ users_to_add, users_to_remove, nb_not_impacted } =
                await callback(chosen_ldap_group));
            group_not_found = false;
        } catch (ex) {
            users_to_add = users_to_remove = [];
            nb_not_impacted = 0;
            group_not_found = true;
        }

        preview.classList.remove("loading");

        preview.insertAdjacentHTML(
            "beforeEnd",
            mustache.render(
                preview_template,
                getPresenter(
                    users_to_add,
                    users_to_remove,
                    display_name,
                    chosen_ldap_group,
                    nb_not_impacted,
                    group_not_found,
                ),
            ),
        );

        button.disabled = group_not_found;
    }

    function removeAllChildren(element) {
        [...element.children].forEach((child) => child.remove());
    }

    function getPresenter(
        users_to_add,
        users_to_remove,
        ugroup_name,
        chosen_ldap_group,
        nb_not_impacted,
        ldap_group_not_found,
    ) {
        const nb_to_add = users_to_add.length;
        const nb_to_remove = users_to_remove.length;

        const title = sprintf(
            gettext_provider.gettext("Binding %s to directory group %s"),
            ugroup_name,
            chosen_ldap_group,
        );
        const nb_to_add_text = sprintf(
            gettext_provider.ngettext("%d user to add.", "%d users to add.", nb_to_add),
            nb_to_add,
        );
        const nb_to_remove_text = sprintf(
            gettext_provider.ngettext("%d user to remove.", "%d users to remove.", nb_to_remove),
            nb_to_remove,
        );

        const nothing_to_do_text = gettext_provider.gettext(
            "There aren't any users to add nor to remove.",
        );
        const nb_not_impacted_text = sprintf(
            gettext_provider.ngettext(
                "%d user not impacted.",
                "%d users not impacted.",
                nb_not_impacted,
            ),
            nb_not_impacted,
        );
        const ldap_group_not_found_text = sprintf(
            gettext_provider.gettext("Directory group %s does not exist."),
            chosen_ldap_group,
        );

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
            nb_not_impacted_text,
            ldap_group_not_found,
            ldap_group_not_found_text,
        };
    }
}
