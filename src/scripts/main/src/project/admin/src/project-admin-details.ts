/**
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

import { createModal, openTargetModalIdOnClick } from "tlp";
import { autocomplete_projects_for_select2 as autocomplete } from "@tuleap/autocomplete-for-select2";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import type { GetText } from "@tuleap/gettext";
import { initIconPicker } from "./helpers/icon-picker-initializer";
import { buildIconPicker } from "./helpers/icon-picker-builder";

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

    initTOSCheckbox();
    initHierarchyModal();
    initWarningRestrictedUsersRemovalOnProjectVisibilityChange();
    initIconPicker(document, buildIconPicker(gettext_provider, document));

    const select_element = document.getElementById(
        "project-admin-details-hierarchy-project-select",
    );
    if (!select_element) {
        return;
    }
    autocomplete(select_element, {
        include_private_projects: true,
    });
});

function initHierarchyModal(): void {
    openTargetModalIdOnClick(document, "project-admin-details-hierarchy-delete-button");
}

function initTOSCheckbox(): void {
    const select_element = document.getElementById("project_visibility");
    if (!select_element) {
        return;
    }
    select_element.addEventListener("change", () => {
        const term_service_element = document.getElementById("term-of-service");
        if (!(term_service_element instanceof HTMLInputElement)) {
            throw new Error("No term of service element");
        }
        const term_service_usage_element = document.getElementById("term-of-service-usage");
        if (!term_service_usage_element) {
            throw new Error("No term of service usage element");
        }
        term_service_element.required = true;
        term_service_usage_element.style.display = "block";
    });
}

function initWarningRestrictedUsersRemovalOnProjectVisibilityChange(): void {
    const warning_restricted_users_removal_modal_element = document.getElementById(
        "modal-warning-restricted-users-removal",
    );
    if (!warning_restricted_users_removal_modal_element) {
        return;
    }
    const nb_restricted_user =
        warning_restricted_users_removal_modal_element.dataset.nbRestrictedUsersInProject;
    if (!nb_restricted_user) {
        throw new Error("No number of restricted user in project");
    }
    const number_of_restricted_users_in_project = parseInt(nb_restricted_user, 10);
    if (
        Number.isNaN(number_of_restricted_users_in_project) ||
        number_of_restricted_users_in_project <= 0
    ) {
        return;
    }

    const confirm_button = document.getElementById(
        "modal-warning-restricted-users-removal-confirm",
    );
    if (!confirm_button) {
        return;
    }

    const project_info_form = document.getElementById("project_info_form");
    if (!(project_info_form instanceof HTMLFormElement)) {
        return;
    }

    let has_submission_been_confirmed = false;
    confirm_button.addEventListener("click", () => {
        has_submission_been_confirmed = true;
        project_info_form.submit();
    });

    project_info_form.addEventListener("submit", (event) => {
        if (has_submission_been_confirmed) {
            return;
        }

        const project_visibility_input = project_info_form.elements.namedItem("project_visibility");
        if (!(project_visibility_input instanceof HTMLSelectElement)) {
            return;
        }

        // Only display warning if we go to Project::ACCESS_PRIVATE_WO_RESTRICTED
        if (project_visibility_input.value !== "private-wo-restr") {
            return;
        }

        event.preventDefault();
        const modal = createModal(warning_restricted_users_removal_modal_element, {
            destroy_on_hide: true,
        });
        modal.show();
    });
}
