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

import { modal as createModal } from "tlp";
import { autocomplete_projects_for_select2 as autocomplete } from "../../../tuleap/autocomplete-for-select2.js";

document.addEventListener("DOMContentLoaded", () => {
    initTOSCheckbox();
    initHierarchyModal();
    initWarningRestrictedUsersRemovalOnProjectVisibilityChange();

    const select_element = document.getElementById(
        "project-admin-details-hierarchy-project-select"
    );
    if (!select_element) {
        return;
    }
    autocomplete(select_element, {
        include_private_projects: true,
    });
});

function initHierarchyModal() {
    const button = document.getElementById("project-admin-details-hierarchy-delete-button");
    if (!button) {
        return;
    }

    const modal = createModal(document.getElementById(button.dataset.targetModalId));

    button.addEventListener("click", () => {
        modal.show();
    });
}

function initTOSCheckbox() {
    const select_element = document.getElementById("project_visibility");
    if (!select_element) {
        return;
    }
    select_element.addEventListener("change", () => {
        document.getElementById("term-of-service").required = true;
        document.getElementById("term-of-service-usage").style.display = "block";
    });
}

function initWarningRestrictedUsersRemovalOnProjectVisibilityChange() {
    const warning_restricted_users_removal_modal_element = document.getElementById(
        "modal-warning-restricted-users-removal"
    );
    if (!warning_restricted_users_removal_modal_element) {
        return;
    }
    const number_of_restricted_users_in_project = parseInt(
        warning_restricted_users_removal_modal_element.dataset.nbRestrictedUsersInProject,
        10
    );
    if (
        Number.isNaN(number_of_restricted_users_in_project) ||
        number_of_restricted_users_in_project <= 0
    ) {
        return;
    }

    const confirm_button = document.getElementById(
        "modal-warning-restricted-users-removal-confirm"
    );
    if (!confirm_button) {
        return;
    }

    const project_info_form = document.getElementById("project_info_form");
    if (!project_info_form) {
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

        const project_visibility_input = project_info_form.elements["project_visibility"];
        if (!project_visibility_input) {
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
