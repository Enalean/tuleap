/**
 * Copyright Enalean (c) 2017-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

import { modal as createModal, filterInlineTable } from "tlp";

import { escaper } from "../../tuleap/escaper.js";
import { sanitize } from "dompurify";
import Gettext from "node-gettext";
import french_translations from "../po/fr.po";
import { sprintf } from "sprintf-js";
import { autocomplete_users_for_select2 } from "../../tuleap/autocomplete-for-select2.js";
import { initImportMembersPreview } from "./members-import/project-admin-members-import";

const gettext_provider = new Gettext();

document.addEventListener("DOMContentLoaded", () => {
    gettext_provider.addTranslations("fr_FR", "project-admin", french_translations);
    gettext_provider.setTextDomain("project-admin");
    gettext_provider.setLocale(document.body.dataset.userLocale);

    initProjectMembersSelect2();
    initMembersFilter();
    initModals();
    initImportMembersPreview();
});

function initModals() {
    document.addEventListener("click", (event) => {
        const button = event.target;
        if (
            button.id === "project-admin-members-modal-import-users-button" ||
            button.classList.contains("project-members-delete-button")
        ) {
            const modal = createModal(document.getElementById(button.dataset.targetModalId), {
                destroy_on_hide: true,
            });

            if (button.classList.contains("project-members-delete-button")) {
                updateDeleteModalContent(button);
            }
            modal.show();
        }
    });
}

function updateDeleteModalContent(button) {
    document.getElementById("project-admin-members-confirm-member-removal-modal-user-id").value =
        button.dataset.userId;
    updateDeleteModalDescription(button);
}

function updateDeleteModalDescription(button) {
    const modal_description = document.getElementById(
        "project-admin-members-confirm-member-removal-modal-description"
    );

    modal_description.innerText = "";
    modal_description.insertAdjacentHTML(
        "afterBegin",
        sanitize(
            sprintf(
                gettext_provider.gettext(
                    "You're about to remove <b>%s</b> from this project. Please confirm your action."
                ),
                escaper.html(button.dataset.name)
            )
        )
    );
}

function initProjectMembersSelect2() {
    const select_element = document.getElementById("project-admin-members-add-user-select");

    if (!select_element) {
        return;
    }

    autocomplete_users_for_select2(select_element, {
        internal_users_only: false,
        project_id: select_element.dataset.projectId,
    });
}

function initMembersFilter() {
    const members_filter = document.getElementById("project-admin-members-list-filter-table");

    if (members_filter) {
        filterInlineTable(members_filter);
    }
}
