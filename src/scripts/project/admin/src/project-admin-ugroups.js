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

import { filterInlineTable, get, modal as createModal } from "tlp";
import { sanitize } from "dompurify";
import Gettext from "node-gettext";
import { sprintf } from "sprintf-js";
import french_translations from "../po/fr.po";
import { escaper } from "../../../tuleap/escaper.js";

const gettext_provider = new Gettext();

document.addEventListener("DOMContentLoaded", () => {
    const member_list_container = document.getElementById(
        "project-admin-user-groups-member-list-container"
    );
    if (member_list_container) {
        gettext_provider.addTranslations("fr_FR", "project-admin", french_translations);
        gettext_provider.setLocale(document.body.dataset.userLocale);
        gettext_provider.setTextDomain("project-admin");
    }

    initModals();
    initModalAddUserToUGroupAndProjectMembers();
    initGroupsFilter();
    initBindingDependencies();
});

function initModals() {
    document.addEventListener("click", (event) => {
        const button = event.target;
        const allowed_ids = [
            "project-admin-ugroup-add-binding",
            "project-admin-ugroups-modal",
            "project-admin-delete-binding",
        ];
        const is_button_id_allowed = allowed_ids.includes(button.id);
        const allowed_classes = [
            "project-admin-delete-ugroups-modal",
            "project-admin-remove-user-from-group",
        ];
        const is_button_classlist_contain_allowed_class = allowed_classes.some((classname) =>
            button.classList.contains(classname)
        );

        if (is_button_id_allowed || is_button_classlist_contain_allowed_class) {
            const modal = createModal(document.getElementById(button.dataset.targetModalId), {
                destroy_on_hide: true,
            });

            if (button.classList.contains("project-admin-remove-user-from-group")) {
                updateDeleteModalContent(button);
            }
            modal.show();
        }
    });
}

function updateDeleteModalContent(button) {
    document.getElementById("project-admin-remove-user-from-group-modal-user-id").value =
        button.dataset.userId;
    updateDeleteModalDescription(button);
    updateDeleteModalButtons(button);
}

function updateDeleteModalDescription(button) {
    const modal_description = document.getElementById(
        "project-admin-remove-user-from-group-modal-description"
    );

    modal_description.innerText = "";
    modal_description.insertAdjacentHTML(
        "afterBegin",
        sanitize(
            sprintf(
                gettext_provider.gettext(
                    "You are about to remove <b>%s</b> from <b>%s</b>. Please, confirm your action."
                ),
                escaper.html(button.dataset.userName),
                escaper.html(button.dataset.ugroupName)
            )
        )
    );
}

function updateDeleteModalButtons(button) {
    const user_is_admin = button.dataset.userIsProjectAdmin === "1";
    const delete_from_project_button = document.getElementById(
        "project-admin-remove-user-from-group-modal-remove-from-project"
    );

    delete_from_project_button.classList.toggle(
        "project-admin-remove-user-from-group-and-project-hidden-button",
        user_is_admin
    );
}

function initModalAddUserToUGroupAndProjectMembers() {
    const button = document.getElementById("project-admin-add-to-ugroup-and-project-members-modal");
    if (!button) {
        return;
    }

    button.addEventListener("click", () => {
        const selected_user = document.getElementById("project-admin-members-add-user-select")
            .value;
        if (!selected_user) {
            return;
        }
        const icon = document.getElementById(
            "project-administration-add-to-ugroup-and-project-members-icon"
        );

        icon.classList.remove("fa-plus");
        icon.classList.add("fa-spin", "fa-spinner");

        document.getElementById("add-user-to-ugroup").value = selected_user;
        initModalOrSendForm(selected_user);
    });
}

function openConfirmationModal(selected_user) {
    const button = document.getElementById("project-admin-add-to-ugroup-and-project-members-modal");
    const modal = createModal(document.getElementById(button.dataset.targetModalId));
    const ugroup_name = document.getElementById("user-group").value;
    const icon = document.getElementById(
        "project-administration-add-to-ugroup-and-project-members-icon"
    );
    const confirmation_message = sprintf(
        gettext_provider.gettext("You are about to add <b>%s</b> in <b>%s</b> users group."),
        selected_user,
        ugroup_name
    );

    document.getElementById(
        "add-user-to-ugroup-and-project-members-confirmation-message"
    ).innerHTML = sanitize(confirmation_message);

    modal.show();

    icon.classList.add("fa-plus");
    icon.classList.remove("fa-spin", "fa-spinner");
}

async function initModalOrSendForm(identifier) {
    const button = document.getElementById("project-admin-add-to-ugroup-and-project-members-modal");
    const project_id = button.dataset.projectId;

    const members_ugroup_id = 3;
    const ugroup_identifier = project_id + "_" + members_ugroup_id;

    const response = await get("/api/v1/user_groups/" + ugroup_identifier + "/users", {
        params: {
            query: JSON.stringify({ identifier }),
        },
    });
    const users = await response.json();

    if (users.length === 0) {
        openConfirmationModal(identifier);
    } else {
        document.getElementById("add-user-to-ugroup-and-project-members").submit();
    }
}

function initGroupsFilter() {
    const groups_filter = document.getElementById("project-admin-ugroups-list-table-filter");
    if (groups_filter) {
        filterInlineTable(groups_filter);
    }
}

function initBindingDependencies() {
    const project_selectbox = document.getElementById("project-admin-ugroup-add-binding-project");
    const ugroup_selectbox = document.getElementById("project-admin-ugroup-add-binding-ugroup");

    if (!project_selectbox || !ugroup_selectbox) {
        return;
    }

    project_selectbox.addEventListener("change", mapUgroupsSelectboxToProjectSelectbox);
    mapUgroupsSelectboxToProjectSelectbox();

    function mapUgroupsSelectboxToProjectSelectbox() {
        let i = ugroup_selectbox.options.length;
        while (--i > 0) {
            ugroup_selectbox.remove(i);
        }

        const selected_option = project_selectbox.options[project_selectbox.selectedIndex];
        if (!selected_option.value) {
            return;
        }

        for (const ugroup of JSON.parse(selected_option.dataset.ugroups)) {
            ugroup_selectbox.options.add(new Option(ugroup["name"], ugroup["id"]));
        }
    }
}
