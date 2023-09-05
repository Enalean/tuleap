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

import { createModal } from "tlp";
import { filterInlineTable } from "@tuleap/filter-inline-table";
import { escaper } from "@tuleap/html-escaper";
import { sanitize } from "dompurify";
import { sprintf } from "sprintf-js";
import { autocomplete_users_for_select2 } from "@tuleap/autocomplete-for-select2";
import { initImportMembersPreview } from "./members-import/project-admin-members-import";
import type { GetText } from "@tuleap/gettext";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";

let gettext_provider: GetText | null;

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

    initProjectMembersSelect2();
    initMembersFilter();
    initModals();
    await initImportMembersPreview();
});

function initModals(): void {
    document.addEventListener("click", (event) => {
        const button = event.target;

        // To be compatible with the icon picker
        if (!(button instanceof HTMLElement)) {
            return;
        }

        if (
            button.id === "project-admin-members-modal-import-users-button" ||
            button.classList.contains("project-members-delete-button") ||
            button.classList.contains("withdraw-invitation-button")
        ) {
            const target_modal_id = button.dataset.targetModalId;
            if (!target_modal_id) {
                throw new Error("No target Modal Id");
            }
            const target_modal_element = document.getElementById(target_modal_id);
            if (!target_modal_element) {
                throw new Error("No target Modal element");
            }
            const modal = createModal(target_modal_element, {
                destroy_on_hide: true,
            });

            if (button.classList.contains("project-members-delete-button")) {
                updateDeleteModalContent(button);
            }
            modal.show();
        }
    });
}

function updateDeleteModalContent(button: HTMLElement): void {
    const button_confirm_remove = document.getElementById(
        "project-admin-members-confirm-member-removal-modal-user-id",
    );
    if (!(button_confirm_remove instanceof HTMLInputElement)) {
        throw new Error("No button to confirm removal user");
    }
    const user_id = button.dataset.userId;
    if (!user_id) {
        throw new Error("No user id");
    }

    button_confirm_remove.value = user_id;
    updateDeleteModalDescription(button);
}

function updateDeleteModalDescription(button: HTMLElement): void {
    const modal_description = document.getElementById(
        "project-admin-members-confirm-member-removal-modal-description",
    );
    if (!modal_description) {
        throw new Error("No modal description");
    }
    const user_name = button.dataset.name;
    if (!user_name) {
        throw new Error("No user name");
    }
    if (!gettext_provider) {
        throw new Error("No gettext provider");
    }

    modal_description.innerText = "";
    modal_description.insertAdjacentHTML(
        "afterbegin",
        sanitize(
            sprintf(
                gettext_provider.gettext(
                    "You're about to remove <b>%s</b> from this project. Please confirm your action.",
                ),
                escaper.html(user_name),
            ),
        ),
    );
}

function initProjectMembersSelect2(): void {
    const select_element = document.getElementById("project-admin-members-add-user-select");

    if (!select_element) {
        return;
    }

    autocomplete_users_for_select2(select_element, {
        internal_users_only: 0,
        use_tuleap_id: true,
        project_id: select_element.dataset.projectId,
    });
}

function initMembersFilter(): void {
    const members_filter = document.getElementById("project-admin-members-list-filter-table");
    if (members_filter instanceof HTMLInputElement) {
        filterInlineTable(members_filter);
    }
}
