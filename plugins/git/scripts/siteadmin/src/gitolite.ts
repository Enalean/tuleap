/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import { autocomplete_projects_for_select2 } from "@tuleap/autocomplete-for-select2";
import { filterInlineTable } from "@tuleap/filter-inline-table";

document.addEventListener("DOMContentLoaded", () => {
    const bindProjectSelectors = (): void => {
        const project_selectors = document.querySelectorAll(".gitolite-project-selector");
        [].forEach.call(project_selectors, function (project_selector) {
            autocomplete_projects_for_select2(project_selector, {
                include_private_projects: true,
            });
        });
    };

    const bindFilter = (): void => {
        const filter = document.getElementById("filter-projects");
        if (filter && filter instanceof HTMLInputElement) {
            filterInlineTable(filter);
        }
    };

    const bindToggleRevokeSelectedButton = (): void => {
        const selectboxes = document.querySelectorAll(
            '#allowed-projects-list input[type="checkbox"]',
        );

        if (!selectboxes) {
            return;
        }

        selectboxes.forEach((selectbox) => {
            selectbox.addEventListener("click", () => {
                const revoke_project = document.getElementById("revoke-project");
                if (!revoke_project) {
                    throw new Error("Revoke project DOM element not found");
                }
                if (
                    document.querySelectorAll(
                        '#allowed-projects-list input[type="checkbox"]:not(#check-all):checked',
                    ).length > 0
                ) {
                    revoke_project.removeAttribute("disabled");
                } else {
                    revoke_project.setAttribute("disabled", "disabled");
                }
            });
        });
    };

    const bindSelectAllCheckbox = (): void => {
        const check_all_checkbox = document.getElementById("check-all");
        if (!(check_all_checkbox instanceof HTMLInputElement)) {
            throw new Error("Gitolite check all is not a select element");
        }
        const project_checkboxes = document.querySelectorAll(
            '#allowed-projects-list input[type="checkbox"]:not(#check-all)',
        );

        if (!check_all_checkbox || !project_checkboxes) {
            return;
        }

        check_all_checkbox.addEventListener("click", () => {
            if (check_all_checkbox.checked) {
                project_checkboxes.forEach((checkbox) => {
                    if (!(checkbox instanceof HTMLInputElement)) {
                        throw new Error(
                            `Gitolite checkbox ${checkbox.id} is not a checkbox element`,
                        );
                    }
                    checkbox.checked = true;
                });
            } else {
                project_checkboxes.forEach((checkbox) => {
                    if (!(checkbox instanceof HTMLInputElement)) {
                        throw new Error(
                            `Gitolite checkbox ${checkbox.id} is not a checkbox element`,
                        );
                    }
                    checkbox.checked = false;
                });
            }
        });

        project_checkboxes.forEach((checkbox) => {
            checkbox.addEventListener("click", () => {
                if (
                    document.querySelectorAll(
                        '#allowed-projects-list input[type="checkbox"]:not(#check-all):not(:checked)',
                    ).length > 0
                ) {
                    check_all_checkbox.checked = false;
                }
            });
        });
    };

    bindSelectAllCheckbox();
    bindToggleRevokeSelectedButton();
    bindProjectSelectors();
    bindFilter();
});
