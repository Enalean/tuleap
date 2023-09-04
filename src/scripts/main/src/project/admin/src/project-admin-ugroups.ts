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

import { get } from "@tuleap/tlp-fetch";
import { createModal } from "tlp";
import { filterInlineTable } from "@tuleap/filter-inline-table";
import { sanitize } from "dompurify";
import { sprintf } from "sprintf-js";
import { escaper } from "@tuleap/html-escaper";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import type { GetText } from "@tuleap/gettext";

let gettext_provider: null | GetText;

document.addEventListener("DOMContentLoaded", async () => {
    const user_locale = document.body.dataset.userLocale;
    if (!user_locale) {
        throw new Error("No user locale");
    }
    gettext_provider = await initGettext(
        user_locale,
        "project-admin",
        (user_locale) =>
            import(
                /* webpackChunkName: "project-admin-po-" */ "../po/" +
                    getPOFileFromLocale(user_locale)
            ),
    );

    initModals();
    initModalAddUserToUGroupAndProjectMembers();
    initGroupsFilter();
    initBindingDependencies();
});

function initModals(): void {
    document.addEventListener("click", (event) => {
        const button = event.target;

        // To be compatible with the icon picker
        if (!(button instanceof HTMLElement)) {
            return;
        }

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
            button.classList.contains(classname),
        );

        if (is_button_id_allowed || is_button_classlist_contain_allowed_class) {
            const modal_id = button.dataset.targetModalId;
            if (!modal_id) {
                throw new Error("No modal id");
            }

            const modal_element = document.getElementById(modal_id);
            if (!modal_element) {
                throw new Error("No modal with id #" + modal_id);
            }

            const modal = createModal(modal_element, {
                destroy_on_hide: true,
            });

            if (button.classList.contains("project-admin-remove-user-from-group")) {
                updateDeleteModalContent(button);
            }
            modal.show();
        }
    });
}

function updateDeleteModalContent(button: HTMLElement): void {
    const modal_remove_user = document.getElementById(
        "project-admin-remove-user-from-group-modal-user-id",
    );
    if (!(modal_remove_user instanceof HTMLInputElement)) {
        throw new Error("No modal to remove user from group");
    }
    const user_id = button.dataset.userId;
    if (!user_id) {
        throw new Error("No data user id");
    }
    modal_remove_user.value = user_id;
    updateDeleteModalDescription(button);
    updateDeleteModalButtons(button);
}

function updateDeleteModalDescription(button: HTMLElement): void {
    const modal_description = document.getElementById(
        "project-admin-remove-user-from-group-modal-description",
    );

    if (!(modal_description instanceof HTMLParagraphElement)) {
        throw new Error("No description in modal to remove user from group");
    }
    const user_name = button.dataset.userName;
    const ugroup_name = button.dataset.ugroupName;
    if (!user_name) {
        throw new Error("No date user name");
    }
    if (!ugroup_name) {
        throw new Error("No date ugroup name");
    }

    modal_description.innerText = "";
    if (!gettext_provider) {
        throw new Error("No gettext provider");
    }
    modal_description.insertAdjacentHTML(
        "afterbegin",
        sanitize(
            sprintf(
                gettext_provider.gettext(
                    "You are about to remove <b>%s</b> from <b>%s</b>. Please, confirm your action.",
                ),
                escaper.html(user_name),
                escaper.html(ugroup_name),
            ),
        ),
    );
}

function updateDeleteModalButtons(button: HTMLElement): void {
    const user_is_admin = button.dataset.userIsProjectAdmin === "1";
    const delete_from_project_button = document.getElementById(
        "project-admin-remove-user-from-group-modal-remove-from-project",
    );
    if (!(delete_from_project_button instanceof HTMLElement)) {
        throw new Error("No button to delete ugroup from project");
    }

    delete_from_project_button.classList.toggle(
        "project-admin-remove-user-from-group-and-project-hidden-button",
        user_is_admin,
    );
}

function initModalAddUserToUGroupAndProjectMembers(): void {
    const button = document.getElementById("project-admin-add-to-ugroup-and-project-members-modal");
    if (!button) {
        return;
    }

    button.addEventListener("click", async () => {
        const selected_user_element = document.getElementById(
            "project-admin-members-add-user-select",
        );
        if (!(selected_user_element instanceof HTMLSelectElement)) {
            throw new Error("No select element to add user");
        }
        const selected_user = selected_user_element.value;
        if (!selected_user) {
            return;
        }
        const selected_user_label =
            selected_user_element.options[selected_user_element.selectedIndex].text;
        const icon = document.getElementById(
            "project-administration-add-to-ugroup-and-project-members-icon",
        );
        if (!(icon instanceof HTMLElement)) {
            throw new Error("No icon to select element to add user");
        }

        icon.classList.remove("fa-plus");
        icon.classList.add("fa-spin", "fa-spinner");

        const user_select = document.getElementById("add-user-to-ugroup");
        if (!(user_select instanceof HTMLInputElement)) {
            throw new Error("No selected user in element to add user");
        }
        user_select.value = selected_user;
        await initModalOrSendForm(selected_user, selected_user_label);
    });
}

function openConfirmationModal(selected_user_label: string): void {
    const button = document.getElementById("project-admin-add-to-ugroup-and-project-members-modal");
    if (!(button instanceof HTMLElement)) {
        throw new Error("No button to add user");
    }
    const target_modal_id = button.dataset.targetModalId;
    if (!target_modal_id) {
        throw new Error("no data target modal id");
    }
    const modal_element = document.getElementById(target_modal_id);
    if (!modal_element) {
        throw new Error("No modal element");
    }
    const modal = createModal(modal_element);
    const ugroup_name_element = document.getElementById("user-group");
    if (!(ugroup_name_element instanceof HTMLInputElement)) {
        throw new Error("No ugroup name element");
    }
    const ugroup_name = ugroup_name_element.value;
    const icon = document.getElementById(
        "project-administration-add-to-ugroup-and-project-members-icon",
    );
    if (!(icon instanceof HTMLElement)) {
        throw new Error("No icon to add user");
    }

    let confirmation_message = "";
    if (!gettext_provider) {
        throw new Error("No gettext provider");
    }
    confirmation_message = sprintf(
        gettext_provider.gettext("You are about to add <b>%s</b> in <b>%s</b> users group."),
        escaper.html(selected_user_label),
        escaper.html(ugroup_name),
    );

    const message_confirmation_add_element = document.getElementById(
        "add-user-to-ugroup-and-project-members-confirmation-message",
    );
    if (!(message_confirmation_add_element instanceof HTMLElement)) {
        throw new Error("No message confirmation after add user");
    }
    message_confirmation_add_element.innerHTML = sanitize(confirmation_message);

    modal.show();

    icon.classList.add("fa-plus");
    icon.classList.remove("fa-spin", "fa-spinner");
}

async function initModalOrSendForm(identifier: string, label: string): Promise<void> {
    const button = document.getElementById("project-admin-add-to-ugroup-and-project-members-modal");
    if (!(button instanceof HTMLElement)) {
        throw new Error("No button to add user");
    }
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
        openConfirmationModal(label);
    } else {
        const form_to_add_user = document.getElementById("add-user-to-ugroup-and-project-members");
        if (!(form_to_add_user instanceof HTMLFormElement)) {
            throw new Error("No form to add user");
        }
        form_to_add_user.submit();
    }
}

function initGroupsFilter(): void {
    const groups_filter = document.getElementById("project-admin-ugroups-list-table-filter");
    if (groups_filter instanceof HTMLInputElement) {
        filterInlineTable(groups_filter);
    }
}

function initBindingDependencies(): void {
    const project_selectbox = document.getElementById("project-admin-ugroup-add-binding-project");
    const ugroup_selectbox = document.getElementById("project-admin-ugroup-add-binding-ugroup");

    if (!project_selectbox || !ugroup_selectbox) {
        return;
    }

    project_selectbox.addEventListener("change", mapUgroupsSelectboxToProjectSelectbox);
    mapUgroupsSelectboxToProjectSelectbox();

    function mapUgroupsSelectboxToProjectSelectbox(): void {
        if (
            !(project_selectbox instanceof HTMLSelectElement) ||
            !(ugroup_selectbox instanceof HTMLSelectElement)
        ) {
            return;
        }
        let i = ugroup_selectbox.options.length;
        while (--i > 0) {
            ugroup_selectbox.remove(i);
        }

        const selected_option = project_selectbox.options[project_selectbox.selectedIndex];
        if (!selected_option.value) {
            return;
        }
        const ugroups = selected_option.dataset.ugroups;
        if (!ugroups) {
            return;
        }

        for (const ugroup of JSON.parse(ugroups)) {
            ugroup_selectbox.options.add(new Option(ugroup.name, ugroup.id));
        }
    }
}
